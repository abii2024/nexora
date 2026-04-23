<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * US-08 AC-4: stuurt database-notification naar een zorgbegeleider die
 * zojuist aan een cliënt gekoppeld is.
 *
 * Channel = database-only. E-mail-notificatie is expliciet out-of-scope
 * voor US-08 (zie docs/verbetervoorstellen.md — komt in latere iteratie).
 */
class ClientCaregiverAssignedNotification extends Notification
{
    public function __construct(
        public readonly Client $client,
        public readonly string $role,
        public readonly User $assignedBy,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'client_toegewezen',
            'client_id' => $this->client->id,
            'client_name' => $this->client->fullName(),
            'role' => $this->role,
            'assigned_by_user_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
        ];
    }
}
