<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\OfferCalculation;
use App\Models\OfferEmail;
use App\Models\OfferFile;
use App\Models\ProjectOffer;
use App\Models\ProjectService;
use App\Models\User;
use App\Traits\Emails;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectOfferController extends Controller
{
    use Emails;

    public function index()
    {
        $offers = ProjectOffer::latest()->paginate(10);

        return view('admin.project_offers.index', compact('offers'));
    }

    public function show($offer)
    {

        $offer = ProjectOffer::with([
            'calculations',
            'files',
            'emails.files',
            'assignedUser',
        ])->findOrFail($offer);

        return view('admin.project_offers.show', compact('offer'));
    }

    public function create()
    {
        $users = User::all();

        return view('admin.project_offers.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_user_id' => 'nullable|exists:users,id',
            'emails.*.sender' => 'nullable|string|max:255',
            'emails.*.recipient' => 'nullable|string|max:255',
            'emails.*.subject' => 'nullable|string|max:255',
            'emails.*.body' => 'nullable|string',
            'emails.*.attachments' => 'array',
            'files.*.file_name' => 'nullable|string|max:255',
            'files.*.description' => 'nullable|string',
            'files.*.file' => 'nullable|file',
        ]);

        $offer = ProjectOffer::create([
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
        ]);

        // 🔹 Store uploaded files (direct offer files)
        if ($request->has('files')) {
            foreach ($request->file('files') as $fileData) {

                if (empty($fileData['file'])) {
                    continue;
                }

                $uploadedFile = $fileData['file'];
                $filePath = $uploadedFile->store('project_offers/files', 'public');

                OfferFile::create([
                    'project_offer_id' => $offer->id,
                    'file_name' => $fileData['file_name'] ?? $uploadedFile->getClientOriginalName(),
                    'description' => $fileData['description'] ?? null,
                    'file_path' => $filePath,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        // 🔹 Add manually entered emails
        if (! empty($data['emails'])) {
            foreach ($data['emails'] as $emailData) {
                $email = $offer->emails()->create([
                    'sender' => $emailData['sender'] ?? null,
                    'recipient' => $emailData['recipient'] ?? null,
                    'subject' => $emailData['subject'] ?? '',
                    'body' => $emailData['body'] ?? '',
                    'direction' => 'inbound',
                ]);

                // Check if files are attached to this email
                if (! empty($emailData['attachments'])) {
                    foreach ($emailData['attachments'] as $attachment) {
                        // Skip if no file uploaded
                        if (! isset($attachment['file'])) {
                            continue;
                        }

                        $uploadedFile = $attachment['file'];
                        $filePath = $uploadedFile->store('project_offers/email_attachments', 'public');

                        OfferFile::create([
                            'project_offer_id' => $offer->id,
                            'offer_email_id' => $email->id,
                            'file_name' => $attachment['file_name'] ?? $uploadedFile->getClientOriginalName(),
                            'description' => $attachment['description'] ?? null,
                            'file_path' => $filePath,
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
        }

        // 🔔 Create admin notification
        Notification::create([
            'user_id' => $offer->assigned_user_id ?? auth()->id(),
            'type' => 'project_offer_created',
            'message' => 'Neues Auftragsangebot erstellt: '.$offer->subject,
            'url' => route('admin.project_offers.show', $offer),
        ]);

        return redirect()
            ->route('admin.project_offers.show', $offer)
            ->with('success', 'Auftragsangebot wurde erfolgreich erstellt.');
    }

    public function edit($offer)
    {
        $offer = ProjectOffer::findOrFail($offer);
        $users = User::all();
        $offer->load(['emails.files', 'files', 'assignedUser']);

        return view('admin.project_offers.edit', compact('offer', 'users'));
    }

    public function update(Request $request, $offer)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_user_id' => 'nullable|exists:users,id',

            // Emails
            'emails.*.id' => 'nullable|exists:offer_emails,id',
            'emails.*.sender' => 'nullable|string|max:255',
            'emails.*.recipient' => 'nullable|string|max:255',
            'emails.*.subject' => 'nullable|string|max:255',
            'emails.*.body' => 'nullable|string',

            // Attachments
            'emails.*.attachments.*.id' => 'nullable|exists:offer_files,id',
            'emails.*.attachments.*.file_name' => 'nullable|string|max:255',
            'emails.*.attachments.*.description' => 'nullable|string',
            'emails.*.attachments.*.file' => 'nullable|file',

            // Direct files
            'files.*.id' => 'nullable|exists:offer_files,id',
            'files.*.file_name' => 'nullable|string|max:255',
            'files.*.description' => 'nullable|string',
            'files.*.file' => 'nullable|file',
        ]);

        $offer = ProjectOffer::findOrFail($offer);

        // 🔹 Update offer main fields
        $offer->update([
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
        ]);

        /* ============================================================
           🔹 HANDLE EMAILS (Update, Delete Missing, Add New)
           ============================================================ */
        $existingEmailIds = $offer->emails->pluck('id')->toArray();
        $submittedEmailIds = collect($data['emails'] ?? [])->pluck('id')->filter()->toArray();

        // Delete removed emails
        foreach ($offer->emails as $email) {
            if (! in_array($email->id, $submittedEmailIds)) {
                $email->attachments()->delete();
                $email->delete();
            }
        }

        // Add/update emails
        if (! empty($data['emails'])) {
            foreach ($data['emails'] as $emailData) {

                // 🔸 Update existing
                if (! empty($emailData['id'])) {
                    $email = OfferEmail::find($emailData['id']);
                    $email->update([
                        'sender' => $emailData['sender'] ?? null,
                        'recipient' => $emailData['recipient'] ?? null,
                        'subject' => $emailData['subject'] ?? '',
                        'body' => $emailData['body'] ?? '',
                    ]);
                }
                // 🔸 Create new
                else {
                    $email = $offer->emails()->create([
                        'sender' => $emailData['sender'] ?? null,
                        'recipient' => $emailData['recipient'] ?? null,
                        'subject' => $emailData['subject'] ?? '',
                        'body' => $emailData['body'] ?? '',
                        'direction' => 'inbound',
                    ]);
                }

                /* Attachments */
                if (! empty($emailData['attachments'])) {
                    foreach ($emailData['attachments'] as $att) {

                        // Update existing attachment
                        if (! empty($att['id'])) {
                            $file = OfferFile::find($att['id']);
                            $file->update([
                                'file_name' => $att['file_name'] ?? $file->file_name,
                                'description' => $att['description'] ?? $file->description,
                            ]);

                            if (! empty($att['file'])) {
                                $path = $att['file']->store('project_offers/email_attachments', 'public');
                                $file->update(['file_path' => $path]);
                            }
                        }

                        // Add new attachment
                        elseif (! empty($att['file'])) {
                            $path = $att['file']->store('project_offers/email_attachments', 'public');

                            OfferFile::create([
                                'project_offer_id' => $offer->id,
                                'offer_email_id' => $email->id,
                                'file_name' => $att['file_name'] ?? $att['file']->getClientOriginalName(),
                                'description' => $att['description'] ?? null,
                                'file_path' => $path,
                                'uploaded_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }
        }

        /* ============================================================
           🔹 DIRECT FILES (Offer-level files)
           ============================================================ */
        $existingFileIds = $offer->files()->whereNull('offer_email_id')->pluck('id')->toArray();
        $submittedFileIds = collect($data['files'] ?? [])->pluck('id')->filter()->toArray();

        // Delete removed files
        foreach ($offer->files()->whereNull('offer_email_id')->get() as $file) {
            if (! in_array($file->id, $submittedFileIds)) {
                $file->delete();
            }
        }

        // Add/update files
        if (! empty($data['files'])) {
            foreach ($data['files'] as $fileInput) {

                if (! empty($fileInput['id'])) {
                    // update
                    $file = OfferFile::find($fileInput['id']);
                    $file->update([
                        'file_name' => $fileInput['file_name'] ?? $file->file_name,
                        'description' => $fileInput['description'] ?? $file->description,
                    ]);

                    if (! empty($fileInput['file'])) {
                        $path = $fileInput['file']->store('project_offers/files', 'public');
                        $file->update(['file_path' => $path]);
                    }
                } elseif (! empty($fileInput['file'])) {
                    // create new
                    $path = $fileInput['file']->store('project_offers/files', 'public');

                    OfferFile::create([
                        'project_offer_id' => $offer->id,
                        'file_name' => $fileInput['file_name'] ?? $fileInput['file']->getClientOriginalName(),
                        'description' => $fileInput['description'] ?? null,
                        'file_path' => $path,
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }
        }

        return redirect()->route('admin.project_offers.show', $offer)
            ->with('success', 'Auftragsangebot wurde erfolgreich aktualisiert.');
    }

    public function destroy($offer)
    {
        $offer = ProjectOffer::findOrFail($offer);
        $offer->delete();

        return redirect()->route('admin.project_offers.index')->with('success', 'Offer deleted successfully.');
    }

    public function addCalculation(Request $request, ProjectOffer $offer)
    {
        $data = $request->validate([
            'field_name' => 'required|string',
            'field_value' => 'required',
            'notes' => 'nullable|string',
        ]);

        $offer->calculations()->create([
            'field_name' => $data['field_name'],
            'field_value' => $data['field_value'],
            'notes' => $data['notes'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Calculation added!');
    }

    public function emailTemplates(ProjectOffer $offer)
    {
        // Get all active email templates with type 'project_offers'
        $templates = EmailTemplate::where('template_type', 'project_offers')
            ->where('active', true)
            ->get();

        return view('admin.project_offers.email_templates.select', compact('offer', 'templates'));
    }

    public function emailPreview(ProjectOffer $offer, ?EmailTemplate $template)
    {
        $offer->load(['calculations']);
        // dd($offer->calculations);
        if (! $template) {
            $subject = '';
            $body = '';
        } else {
            // Generate HTML table for calculations
            $calculationTable = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $calculationTable .= '<thead>
                <tr style="background-color:#f0f0f0;">
                    <th>#</th>
                    <th>Bezeichnung</th>
                    <th>Stück</th>
                    <th>Einzelpreis (€)</th>
                    <th>Gesamt (€)</th>
                </tr>
            </thead>';
            $calculationTable .= '<tbody>';

            foreach ($offer->calculations as $index => $calc) {
                $calculationTable .= '<tr>
                    <td>'.($index + 1).'</td>
                    <td>'.$calc->designation.'</td>
                    <td>'.$calc->pieces.'</td>
                    <td>'.number_format($calc->offer_cost, 2, ',', '.').'</td>
                    <td>'.number_format($calc->gesamt_angebot, 2, ',', '.').'</td>
                </tr>';
            }

            $calculationTable .= '</tbody></table>';
            $subject = str_replace(
                ['[offer_id]', '[offer_customer]', '[offer_subject]'],
                [$offer->id, $offer->customer_name, $offer->subject],
                $template->subject
            );

            $body = str_replace(
                ['[offer_id]', '[offer_customer_name]', '[offer_calculation_total]', '[offer_calculation_table]'],
                [
                    $offer->id,
                    $offer->customer_name,
                    $offer->calculations->sum(fn ($c) => $c->gesamt_angebot),
                    $calculationTable,
                ],
                $template->description
            );
        }

        return view('admin.project_offers.email_templates.preview', compact('subject', 'body', 'offer', 'template'));
    }

    public function addEmail(Request $request, ProjectOffer $offer)
    {
        $this->saveOfferEmail($offer, $request);

        return back()->with('success', 'Offer email added successfully!');
    }

    public function editEmail(OfferEmail $email)
    {
        return view('admin.project_offers.edit_email', compact('email'));
    }

    public function updateEmail(Request $request, OfferEmail $email)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'pdf' => 'nullable|file|mimes:pdf|max:20480',
            'sender' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('pdf')) {
            if ($email->pdf_path) {
                Storage::disk('public')->delete($email->pdf_path);
            }
            $data['pdf_path'] = $request->file('pdf')->store('offer_emails', 'public');
        }

        $email->update($data);

        Notification::create([
            'user_id' => auth()->id(),
            'type' => 'offer_email_updated',
            'message' => 'Offer email updated: '.$email->subject,
            'url' => route('admin.project_offers.show', $email->project_offer_id),
        ]);

        return redirect()
            ->route('admin.project_offers.show', $email->project_offer_id)
            ->with('success', 'Offer email updated successfully!');
    }

    public function sendEmail(Request $request, ProjectOffer $offer)
    {
        $this->saveOfferEmail($offer, $request, 'outbound');

        return redirect()->back()->with('success', 'Successfully Sent Email!');
    }

    public function destroyEmail(ProjectOffer $offer, OfferEmail $email)
    {
        $email->delete();

        return redirect()->route('admin.project_offers.edit', $offer)->with('success', 'Email Deleted successfully!');
    }

    public function destroyFile(ProjectOffer $offer, OfferFile $file)
    {
        $file->delete();

        return redirect()->route('admin.project_offers.edit', $offer)->with('success', 'File Deleted successfully!');
    }

    public function addFile(Request $request, ProjectOffer $offer)
    {
        $data = $request->validate([
            'file_name' => 'required|string|max:255',
            'file' => 'required|file|max:20480',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        $data['file_path'] = $request->file('file')->store('offer_files', 'public');
        $offer->files()->create($data);

        Notification::create([
            'user_id' => auth()->id(),
            'type' => 'offer_file_added',
            'message' => 'New file added to offer: '.$data['file_name'],
            'url' => route('admin.project_offers.show', $offer->id),
        ]);

        return back()->with('success', 'File added successfully!');
    }

    public function calculations(ProjectOffer $offer)
    {
        // Ensure offer has a single main calculation entry
        $calculations = $offer->calculations()->get();

        return view('admin.project_offers.calculations', compact('offer', 'calculations'));
    }

    public function calculationComplete(ProjectOffer $offer)
    {
        // 🔔 Create admin notification
        Notification::create([
            'user_id' => $offer->assigned_user_id ?? auth()->id(),
            'type' => 'project_offer_calculated',
            'message' => 'Auftragsangebot kalkuliert: '.$offer->subject,
            'url' => route('admin.project_offers.calculations', $offer),
        ]);

        return redirect()->back()->with('success', 'Neue Mitteillung geschickt!');
    }

    public function calculationPdf(ProjectOffer $offer)
    {

        $pdf = Pdf::loadView('admin.project_offers.calculation.pdf', compact('offer'));

        return $pdf->download('calculation_'.$offer->id.'.pdf');
    }

    public function showCalculation(ProjectOffer $offer, OfferCalculation $calculation)
    {
        // Load items relationship
        $calculation->load('items.service'); // optional: eager load project service

        return view('admin.project_offers.items.show', [
            'offer' => $offer,
            'calculation' => $calculation,
        ]);
    }

    public function createItems(ProjectOffer $offer)
    {
        $rootServices = ProjectService::whereNull('parent_id')->get();

        return view('admin.project_offers.items.create', [
            'offer' => $offer,
            'rootServices' => $rootServices,
        ]);
    }

    public function storeItems(Request $request, ProjectOffer $offer)
    {
        $request->validate([
            'designation' => 'required|string',
            't_pieces' => 'required|numeric|min:1',
            'extra_tax' => 'nullable|numeric',
            'final_offer' => 'nullable|numeric',
            'service_id.*' => 'nullable|exists:project_services,id',
            'hours.*' => 'nullable|numeric',
            'price_per_hour.*' => 'nullable|numeric',
            'pieces.*' => 'nullable|numeric',
            'price_per_unit.*' => 'nullable|numeric',
            'comment.*' => 'nullable|string',
            'cost_type.*' => 'nullable|in:cost,material,fremd_leistung',
        ]);

        // 1️⃣ Create new parent calculation record
        $calculation = $offer->calculations()->create([
            'designation' => $request->designation,
            'pieces' => $request->t_pieces,
            'total_cost' => 0,
        ]);

        $total_sum = 0;
        $materialCost = 0;
        $externalCost = 0;
        $total_hours = 0;

        // 2️⃣ Loop all items at once
        foreach ($request->service_id as $i => $serviceId) {

            $pieces = $request->pieces[$i] ?? 0;
            $unitPrice = $request->price_per_unit[$i] ?? 0;

            $hours = $request->hours[$i] ?? 0;
            $hourPrice = $request->price_per_hour[$i] ?? 0;

            $total_hours += $hours;

            $itemTotal = ($pieces * $unitPrice) + ($hours * $hourPrice);

            $costType = $request->cost_type[$i] ?? 'cost';

            if ($costType === 'cost') {
                $total_sum += $itemTotal;
            } elseif ($costType === 'material') {
                $materialCost += $itemTotal;
            } elseif ($costType === 'fremd_leistung') {
                $externalCost += $itemTotal;
            }

            $calculation->items()->create([
                'project_service_id' => $serviceId ?? 1,
                'hours' => $request->hours[$i] ?? 0,
                'price_per_hour' => $request->price_per_hour[$i] ?? 0,
                'pieces' => $pieces,
                'price_per_unit' => $unitPrice,
                'cost_type' => $costType,
                'total' => $itemTotal,
                'comment' => $request->comment[$i] ?? null,
            ]);
        }

        $total_cost = $calculation->total_cost + $total_sum + $materialCost + $externalCost;
        if ($request->filled('extra_tax')) {
            $total_cost += $total_cost * ($request->extra_tax / 100);
        }

        $final_offer = 0;
        if ($request->filled('final_offer')) {
            $final_offer = $total_cost + ($total_cost * ($request->final_offer / 100));
        } else {
            $final_offer += $total_cost;
        }

        // 3️⃣ Update calculation total after adding all items
        $calculation->update([
            'cost' => round($total_sum),
            'hours' => $total_hours,
            'material_cost' => round($materialCost),
            'external_cost' => round($externalCost),
            'total_cost' => round($total_cost, -1),
            'offer_cost' => round($final_offer, -1),
            'created_by' => auth()->id(),
            'final_offer' => $request->final_offer,
            'extra_tax' => $request->extra_tax,
        ]);

        return redirect()
            ->route('admin.project_offers.calculations', $offer->id)
            ->with('success', 'Kalkulationspositionen gespeichert.');
    }

    public function editItems(ProjectOffer $offer, OfferCalculation $calculation)
    {
        $rootServices = ProjectService::whereNull('parent_id')->get();
        $items = $calculation->items()->with('service')->get();

        return view('admin.project_offers.items.edit', [
            'offer' => $offer,
            'calculation' => $calculation,
            'items' => $items,
            'rootServices' => $rootServices,
        ]);
    }

    public function updateItems(Request $request, ProjectOffer $offer, OfferCalculation $calculation)
    {
        $request->validate([
            'designation' => 'required|string',
            't_pieces' => 'required|numeric|min:1',
            'extra_tax' => 'nullable|numeric',
            'final_offer' => 'nullable|numeric',
            'service_id.*' => 'nullable|exists:project_services,id',
            'hours.*' => 'nullable|numeric',
            'price_per_hour.*' => 'nullable|numeric',
            'pieces.*' => 'nullable|numeric',
            'price_per_unit.*' => 'nullable|numeric',
            'comment.*' => 'nullable|string',
            'cost_type.*' => 'nullable|in:cost,material,fremd_leistung',
        ]);

        // Delete old items
        $calculation->items()->delete();

        $total_sum = $materialCost = $externalCost = $total_hours = 0;

        foreach ($request->service_id as $i => $serviceId) {
            $pieces = $request->pieces[$i] ?? 0;
            $unitPrice = $request->price_per_unit[$i] ?? 0;
            $hours = $request->hours[$i] ?? 0;
            $hourPrice = $request->price_per_hour[$i] ?? 0;

            $itemTotal = ($pieces * $unitPrice) + ($hours * $hourPrice);

            $costType = $request->cost_type[$i] ?? 'cost';

            if ($costType === 'cost') {
                $total_sum += $itemTotal;
            } elseif ($costType === 'material') {
                $materialCost += $itemTotal;
            } elseif ($costType === 'fremd_leistung') {
                $externalCost += $itemTotal;
            }

            $total_hours += $hours;

            $calculation->items()->create([
                'project_service_id' => $serviceId ?? 1,
                'hours' => $hours,
                'price_per_hour' => $hourPrice,
                'pieces' => $pieces,
                'price_per_unit' => $unitPrice,
                'cost_type' => $costType,
                'total' => $itemTotal,
                'comment' => $request->comment[$i] ?? null,
            ]);
        }

        $total_cost = $total_sum + $materialCost + $externalCost;

        if ($request->filled('extra_tax')) {
            $total_cost += $total_cost * ($request->extra_tax / 100);
        }

        $final_offer = $request->filled('final_offer')
            ? $total_cost + ($total_cost * ($request->final_offer / 100))
            : $total_cost;

        $calculation->update([
            'designation' => $request->designation,
            'pieces' => $request->t_pieces,
            'cost' => round($total_sum),
            'hours' => $total_hours,
            'material_cost' => round($materialCost),
            'external_cost' => round($externalCost),
            'total_cost' => round($total_cost, -1),
            'offer_cost' => round($final_offer, -1),
            'final_offer' => $request->final_offer,
            'extra_tax' => $request->extra_tax,
        ]);

        return redirect()
            ->route('admin.project_offers.calculations', $offer->id)
            ->with('success', 'Kalkulationspositionen aktualisiert.');
    }

    public function duplicateItems(ProjectOffer $offer, OfferCalculation $calculation)
    {
        // Duplicate the calculation
        $newCalc = $calculation->replicate();
        $newCalc->created_at = now();
        $newCalc->updated_at = now();
        $newCalc->save();

        // Duplicate items
        foreach ($calculation->items as $item) {
            $newItem = $item->replicate();
            $newItem->offer_calculation_id = $newCalc->id;
            $newItem->save();
        }

        return redirect()->route('admin.project_offers.items.edit', [$offer, $newCalc])
            ->with('success', 'Calculation duplicated successfully.');
    }

    public function destroyItems(ProjectOffer $offer, OfferCalculation $calculation)
    {
        // Delete calculation & its items (via the model booted method)
        $calculation->delete();

        return redirect()->route('admin.project_offers.calculations', $offer->id)
            ->with('success', 'Kalkulation und alle zugehörigen Positionen wurden gelöscht.');
    }

    public function loadChildServices($parentId)
    {
        $children = ProjectService::where('parent_id', $parentId)
            ->where('active', 1)->get();

        return response()->json($children);
    }

    private function updateTotals(OfferCalculation $calculation)
    {
        $sum = $calculation->items()->sum('total');

        $calculation->update([
            'total_cost' => $sum,
            'offer_cost' => $sum,
        ]);
    }
}
