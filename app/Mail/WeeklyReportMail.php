<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private array $reportData,
        private CarbonInterface $from,
        private CarbonInterface $to
    ) {
        $this->onQueue('reports');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Weekly NewsRoom Report — {$this->from->format('M d')} to {$this->to->format('M d, Y')}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-report',
            with: [
                'articles' => $this->reportData,
                'periodFrom' => $this->from->format('F d, Y'),
                'periodTo' => $this->to->format('F d, Y'),
                'articleCount' => count($this->reportData),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
