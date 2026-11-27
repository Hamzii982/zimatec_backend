<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->name }} · {{ $project->project_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f7; color: #1b1f24; font-family: Arial, sans-serif; }
        .print-toolbar {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            border-bottom: 1px solid #dfe3e8; background: #fff; padding: .8rem 1rem;
        }
        .print-context { min-width: 0; }
        .print-context strong { display: block; overflow-wrap: anywhere; }
        .print-context span { color: #667085; font-size: .85rem; }
        .print-button {
            flex: 0 0 auto; border: 0; border-radius: 7px; background: #1b1f24; color: #fff;
            cursor: pointer; font-size: .9rem; font-weight: 600; padding: .55rem .9rem;
        }
        .print-content { display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 66px); padding: 1rem; }
        .print-image { display: block; max-width: 100%; max-height: calc(100vh - 100px); object-fit: contain; }
        .print-frame { width: 100%; height: calc(100vh - 100px); border: 0; background: #fff; }
        .print-xml { width: 100%; margin: 0; padding: 1rem; overflow-wrap: anywhere; white-space: pre-wrap; background: #fff; font: 12px/1.5 monospace; }
        .print-error { color: #a61b1b; }
        @page { margin: 10mm; }
        @media print {
            body { background: #fff; }
            .print-toolbar { display: none; }
            .print-content { display: block; min-height: 0; padding: 0; }
            .print-image { max-width: 100%; max-height: none; margin: 0 auto; }
            .print-frame { width: 100%; height: 100vh; }
            .print-xml { padding: 0; }
        }
    </style>
</head>
<body>
    <header class="print-toolbar">
        <div class="print-context">
            <strong>{{ $document->name }}</strong>
            <span>{{ $project->project_name }}</span>
        </div>
        <button class="print-button" type="button" onclick="window.print()">
            {{ __('fraesdata.print') }}
        </button>
    </header>

    <main class="print-content">
        @if(in_array($extension, ['jpg', 'jpeg'], true))
            <img id="print-image" class="print-image" src="{{ $fileUrl }}" alt="{{ $document->name }}">
        @elseif($extension === 'xml')
            <pre id="print-xml" class="print-xml">{{ __('fraesdata.print_loading') }}</pre>
        @else
            <iframe id="print-frame" class="print-frame" src="{{ $fileUrl }}" title="{{ $document->name }}"></iframe>
        @endif
    </main>

    <script>
        const fileType = @json($extension);
        const fileUrl = @json($fileUrl);
        const printError = @json(__('fraesdata.print_error'));

        function openPrintDialog() {
            window.setTimeout(() => window.print(), 400);
        }

        if (fileType === 'jpg' || fileType === 'jpeg') {
            const image = document.getElementById('print-image');
            if (image.complete) {
                if (image.naturalWidth > 0) {
                    openPrintDialog();
                } else {
                    document.querySelector('.print-content').textContent = printError;
                }
            } else {
                image.addEventListener('load', openPrintDialog, { once: true });
                image.addEventListener('error', () => {
                    document.querySelector('.print-content').textContent = printError;
                }, { once: true });
            }
        } else if (fileType === 'xml') {
            fetch(fileUrl, { headers: { Accept: 'application/xml,text/xml' } })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(printError);
                    }

                    return response.text();
                })
                .then((content) => {
                    document.getElementById('print-xml').textContent = content;
                    openPrintDialog();
                })
                .catch(() => {
                    const preview = document.getElementById('print-xml');
                    preview.textContent = printError;
                    preview.classList.add('print-error');
                });
        } else {
            document.getElementById('print-frame').addEventListener('load', openPrintDialog, { once: true });
        }
    </script>
</body>
</html>
