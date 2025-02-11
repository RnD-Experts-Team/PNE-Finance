<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickBooksReport extends Model
{
    use HasFactory;

    protected $table = 'quickbooks_reports';

    protected $fillable = [
        'report_name',
        'report_data',
    ];

    protected $casts = [
        'report_data' => 'array',
    ];
}
