<?php
/**
 * FAQ Schema Template for DONAN22.com
 * Include this in post.php or article pages to add FAQ structured data
 * 
 * Usage Example in post.php:
 * 
 * $faqData = [
 *     [
 *         'question' => 'Apakah software ini gratis?',
 *         'answer' => 'Ya, software ini 100% gratis untuk didownload dan digunakan di DONAN22.'
 *     ],
 *     [
 *         'question' => 'Bagaimana cara menginstall?',
 *         'answer' => 'Ikuti panduan instalasi lengkap yang tersedia di halaman download DONAN22. Kami menyediakan tutorial step-by-step.'
 *     ],
 *     // Add more FAQs...
 * ];
 * 
 * include 'includes/faq_schema.php';
 */

// Ensure $faqData is defined before including this file
if (!isset($faqData) || empty($faqData)) {
    return; // Don't output anything if no FAQ data
}

// Build FAQ schema
$faqSchema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => []
];

foreach ($faqData as $faq) {
    if (empty($faq['question']) || empty($faq['answer'])) {
        continue;
    }
    
    $faqSchema['mainEntity'][] = [
        "@type" => "Question",
        "name" => $faq['question'],
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => $faq['answer']
        ]
    ];
}

// Only output if there are valid FAQs
if (!empty($faqSchema['mainEntity'])):
?>
<!-- FAQ Schema Markup -->
<script type="application/ld+json">
<?= json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>
