// وظيفة ذكية لتحميل المكونات (الهيدر والفوتر)
function loadComponent(elementId, fileName) {
    fetch(fileName)
        .then(response => {
            if (!response.ok) throw new Error('لم يتم العثور على الملف');
            return response.text();
        })
        .then(data => {
            document.getElementById(elementId).innerHTML = data;
        })
        .catch(error => console.error('خطأ:', error));
}

// تنفيذ التحميل بمجرد تشغيل الصفحة
document.addEventListener("DOMContentLoaded", () => {
    loadComponent('header-placeholder', 'header.html');
    loadComponent('footer-placeholder', 'footer.html'); // فعل هذا السطر عند إنشاء الفوتر
});