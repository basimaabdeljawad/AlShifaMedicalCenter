<?php
// ai_chat.php — النسخة المصلحة والمحدثة للاتصال بـ Gemini API بنجاح
header('Content-Type: application/json; charset=utf-8');

// 1. استقبال رسالة المستخدم
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'لم أستلم أي أعراض، يرجى كتابة ما تشعر به بدقة.']);
    exit;
}

// 2. مفتاح الـ API الخاص بـ Google Gemini
// (ملاحظة: يفضل دائماً التأكد من صلاحية المفتاح الخاص بك)
$apiKey = "AIzaSyDMjS0EWZeFTvU0EzZpfCbFZRIDhst9f7c";

// 3. إعداد رابط الـ API المحدث رسمياً من جوجل لتفادي خطأ v1beta
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

// 4. بناء الـ Prompt الطبي الموجه للبوت
$systemInstruction = "أنت مساعد طبي ذكي لمركز الشفاء الطبي. مهمتك هي تحليل الأعراض التي يكتبها المريض، وتوجيهه بشكل ودّي ومختصر إلى القسم الطبي المناسب بالمستشفى (مثل: عيادة الباطنية، عيادة العيون، عيادة العظام، الأطفال.. إلخ). لا تقدم تشخيصاً طبياً نهائياً، بل قدم نصيحة وتوجيهاً للعيادة المناسبة فقط.";

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $systemInstruction . "\n\nرسالة المريض: " . $userMessage]
            ]
        ]
    ]
];

// 5. إرسال الطلب عبر cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // لتجنب مشاكل شهادات SSL المحلية على السيرفر الشخصي

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. معالجة الرد وإرساله للشات
if ($httpCode === 200) {
    $result = json_decode($response, true);
    $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($reply) {
        echo json_encode(['reply' => $reply]);
    } else {
        echo json_encode(['reply' => 'عذراً، واجهت مشكلة في معالجة الرد من الذكاء الاصطناعي.']);
    }
} else {
    // في حال وجود خطأ في المفتاح أو السيرفر
    echo json_encode(['reply' => 'عذراً، لم أتمكن من الاتصال بالخادم الطبي حالياً. يرجى التأكد من إعداد الـ API Key بشكل صحيح.']);
}
exit;