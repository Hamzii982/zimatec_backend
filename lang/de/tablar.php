<?php

return [

    'filter' => [
        'low_stock' => 'Niedrigerbestand',
        'empty'     => 'Leere Materialien',
        'status'    => 'Status',
        'reset'     => 'Filter zurücksetzen',
    ],

    'status' => [
        'notified'  => 'Bedarf gemeldet',
        'ordered'   => 'Bestellt',
        'blocked'   => 'Blockiert',
        'delivered' => 'Geliefert',
    ],

    'show' => [
        'recent_supplier'   => 'Bisheriger Lieferant',
        'all_from_supplier' => 'Alle Materialien von diesem Lieferanten',
        'no_supplier'       => 'Kein Lieferant hinterlegt',
        'current_stock'     => 'Aktueller Bestand',
        'add'               => 'Hinzufügen',
        'save'              => 'Speichern',
        'threshold'         => 'Mindestbestand',
        'low_stock_warning' => 'Niedriger Bestand',
        'quantity_updated'  => 'Menge aktualisiert',
        'quantity_error'    => 'Fehler beim Speichern',
        'audit_adjust'     => 'Bestandsanpassen',
        'audit_hint'       => 'Aktuelle Menge dieses Materials aktualisieren.',
        'audit_note'       => 'Hinweis: Diese Aktion wird protokolliert und kann nicht rückgängig gemacht werden.',
        'audit_actual'     => 'Aktuelle Menge',
        'audit_save'       => 'Bestand anpassen',
        'change_status'      => 'Status ändern',
    ],

    'supplier_list' => [
        'title' => 'Materialien — Lieferant: :name',
        'empty' => 'Kein Material in diesem Lager ist diesem Lieferanten zugeordnet.',
        'col'   => [
            'attached_at' => 'Zugewiesen am',
        ],
    ],

];
