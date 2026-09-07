<?php

namespace App\Enums;

// Lista de estados que puede tener una orden
// ENUMS


enum OrderStatus: string {
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
