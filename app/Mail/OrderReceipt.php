<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceipt extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order, public Vendor $vendor, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // The buyer sees the store's name as the sender. The address stays Porrop's so SPF/DKIM keep working.
            from: new Address(config('mail.from.address'), $this->vendor->name),
            // Replies go to the seller, not to Porrop.
            replyTo: $this->vendor->email ? [new Address($this->vendor->email, $this->vendor->name)] : [],
            subject: "Your order {$this->order->reference} from {$this->vendor->name}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-receipt');
    }
}
