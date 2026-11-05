<?php
namespace App\Features\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Features\Stock\Enums\StockStatus;

class Stock extends Model {
    protected $fillable = [
        'user_id', 'name', 'sku', 'description', 'quantity', 
        'unit', 'price', 'min_stock', 'status', 'cover_image_path'
    ];

    protected $casts = [
        'status' => StockStatus::class,
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
