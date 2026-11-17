<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent when a customer asks "find my order". Carries the private link; sent in the store's name. */
class OrderLink extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order, public Vendor $vendor, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), $this->vendor->name),
            replyTo: $this->vendor->email ? [new Address($this->vendor->email, $this->vendor->name)] : [],
            subject: "Your order link: {$this->order->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-link');
    }
}
