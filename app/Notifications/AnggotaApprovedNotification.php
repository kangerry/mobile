<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnggotaApprovedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Keanggotaan Diterima')
            ->greeting('Halo '.$notifiable->nama_anggota)
            ->line('Pengajuan Anda sebagai anggota koperasi telah disetujui.')
            ->line('Terima kasih telah bergabung dengan KOMERA.');
    }
}
