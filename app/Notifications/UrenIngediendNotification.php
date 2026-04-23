<?php

namespace App\Notifications;

use App\Models\Urenregistratie;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database-notification naar alle teamleiders van het team zodra een
 * zorgbegeleider uren indient (US-12 AC-2).
 *
 * Database-only — email is expliciet out-of-scope (zie verbetervoorstellen).
 */
class UrenIngediendNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Urenregistratie $uren,
        protected User $submittedBy,
    ) {}

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
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'uren_ingediend',
            'uren_id' => $this->uren->id,
            'datum' => $this->uren->datum?->format('Y-m-d'),
            'uren' => (string) $this->uren->uren,
            'client_id' => $this->uren->client_id,
            'client_name' => $this->uren->client?->fullName(),
            'submitted_by_user_id' => $this->submittedBy->id,
            'submitted_by_name' => $this->submittedBy->name,
        ];
    }
}
