<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceipt extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order, public Vendor $vendor, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your order {$this->order->reference} from {$this->vendor->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-receipt');
    }
}
