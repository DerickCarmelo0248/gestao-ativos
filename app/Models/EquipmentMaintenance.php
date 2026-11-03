<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EquipmentMaintenance extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array {
        return ['sent_at' => 'datetime', 'returned_at' => 'datetime', 'invoice_required' => 'boolean'];
    }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function sentBy() { return $this->belongsTo(User::class, 'sent_by'); }
    public function returnedBy() { return $this->belongsTo(User::class, 'returned_by'); }
}
