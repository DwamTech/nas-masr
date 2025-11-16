<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserClient extends Model
{
    protected $table = 'user_clients';

    protected $fillable = [
        'user_id',
        'clients',
    ];

    protected $casts = [
        'clients' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function getClientsListAttribute(): array
    {
        return $this->clients ?? [];
    }


    public function addClient(int $clientId): void
    {
        $clients = $this->clients ?? [];
        $clients[] = $clientId;
        $this->clients = $clients;
        $this->save();
    }


    public function removeClient(int $clientId): void
    {
        $clients = $this->clients ?? [];

        $clients = array_values(array_filter($clients, function ($id) use ($clientId) {
            return (int) $id !== $clientId;
        }));

        $this->clients = $clients;
        $this->save();
    }
}
