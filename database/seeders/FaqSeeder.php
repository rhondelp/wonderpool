<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Starter FAQs (editable in admin). Upserts by question.
 */
class FaqSeeder extends Seeder
{
    /**
     * Upserts by question text.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Is the resort exclusive to our group?',
                'answer' => 'Yes. Every booking reserves the whole resort for your group during your package hours.',
            ],
            [
                'question' => 'What packages do you offer?',
                'answer' => 'Day (7:00 AM–5:00 PM), Night (7:00 PM–5:00 AM) and 24-Hour (7:00 AM–5:00 AM the next day). See Packages & Rates for current prices.',
            ],
            [
                'question' => 'How many guests are allowed?',
                'answer' => 'Up to 50 guests per booking. Extra guests may be arranged as an add-on, subject to approval.',
            ],
            [
                'question' => 'How do I confirm my booking?',
                'answer' => 'Pay the downpayment via GCash or bank transfer and upload the receipt. We will approve your booking once the payment is verified.',
            ],
            [
                'question' => 'How long is my slot held while I pay?',
                'answer' => 'Pending bookings without payment proof are released after 24 hours so other guests can book the date.',
            ],
            [
                'question' => 'Can I bring my own food and drinks?',
                'answer' => 'Yes, you are welcome to bring food and drinks. Please keep the pools and gardens clean.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                $faq + ['is_active' => true, 'sort_order' => $index + 1],
            );
        }
    }
}
