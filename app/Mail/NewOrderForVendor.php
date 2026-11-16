<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderForVendor extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order, public Vendor $vendor, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New order {$this->order->reference}: ₦".number_format((float) $this->order->total_amount),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-order-vendor');
    }
}
