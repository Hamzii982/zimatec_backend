<?php

return [

    'filter' => [
        'low_stock' => 'Low stock',
        'empty'     => 'Empty materials',
        'status'    => 'Status',
        'reset'     => 'Reset filters',
    ],

    'status' => [
        'notified'  => 'Reported',
        'ordered'   => 'Ordered',
        'blocked'   => 'Blocked',
        'delivered' => 'Delivered',
    ],

    'show' => [
        'recent_supplier'   => 'Most recent supplier',
        'all_from_supplier' => 'All materials from this supplier',
        'no_supplier'       => 'No supplier attached',
        'current_stock'     => 'Current stock',
        'add'               => 'Add',
        'save'              => 'Save',
        'threshold'         => 'Threshold',
        'low_stock_warning' => 'Low stock',
        'quantity_updated'  => 'Quantity updated',
        'quantity_error'    => 'Error while saving',
        'audit_adjust'     => 'Adjust stock',
        'audit_hint'       => 'Update the current quantity of this material.',
        'audit_note'       => 'Note: This action will be logged and cannot be undone.',
        'audit_actual'     => 'Current Quantity',
        'audit_save'       => 'Adjust Stock',
        'change_status'      => 'Change Status',
    ],

    'supplier_list' => [
        'title' => 'Materials — supplier: :name',
        'empty' => 'No material in this warehouse is attached to this supplier.',
        'col'   => [
            'attached_at' => 'Attached on',
        ],
    ],

];
