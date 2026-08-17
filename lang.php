<!DOCTYPE html>
<html>

<head>
    <title>محول اللغة</title>
    <script src="js/jquery-3.7.1.min.js"></script>
</head>
<br>
<br>
<br>
<br>
<br>
<br>
<br>


<body style=" background: #0000009c; ">
    <form method="post">
        الكلمة الرئيسية:
        <input type="text" name="keyword" id="keyword" required><br><br>
        الكلمة بالإنجليزية:
        <input type="text" name="english_word" required><br><br>
        الكلمة بالعربية:
        <input type="text" name="arabic_word" required><br><br>
        <input type="submit" name="submit" value="اعتماد">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $keyword = strtolower($_POST['keyword']);
        $english_word = $_POST['english_word'];
        $arabic_word = $_POST['arabic_word'];

        $en_file_content = file_get_contents('includes/lang/en.php');
        $ar_file_content = file_get_contents('includes/lang/ar.php');
        if (strpos($en_file_content, '$lang[\'' . $keyword . '\']') === false and strpos($ar_file_content, '$lang[\'' . $keyword . '\']') === false) {
            $en_file = fopen("includes/lang/en.php", "a");
            fwrite($en_file, '$lang[\'' . $keyword . '\'] = "' . $english_word . '";' . PHP_EOL);
            fclose($en_file);

            $ar_file = fopen("includes/lang/ar.php", "a");
            fwrite($ar_file, '$lang[\'' . $keyword . '\'] = "' . $arabic_word . '";' . PHP_EOL);
            fclose($ar_file);

            echo 'Added !<br> <h1 class="copy">{{ lang.' . $keyword . ' }}</h1>';
        } else {
            echo "الكلمة الرئيسية موجودة بالفعل في الملفات.<br><h1 class='copy'>{{ lang.$keyword }}</h1>";
        }
    }
    ?>

    <script>
        $(document).ready(function() {
            // استهداف حقل الإدخال
            var inputField = $("#keyword");

            // استبدال المسافات بعلامات _ عند كتابة المستخدم
            inputField.on("input", function() {
                var text = $(this).val();
                // استبدال جميع المسافات بعلامات _
                var replacedText = text.replace(/\s/g, "_");
                // تحديث قيمة حقل الإدخال
                $(this).val(replacedText);
            });

            $('.copy').click(function() {
                // الحصول على النص المطلوب نسخه
                var textToCopy = $(this).text();

                // إنشاء عنصر نصي مؤقت للنسخ
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(textToCopy).select();

                // تنفيذ أمر النسخ
                document.execCommand('copy');

                // إزالة العنصر المؤقت
                tempInput.remove();

                // تغيير لون النص إلى الأخضر
                $(this).css('background', 'green');
                $(this).css('color', 'white');

                // إرجاع اللون إلى حالته الأصلية بعد ثانيتين (اختياري)
                setTimeout(() => {
                    $(this).css('background', '');
                    $(this).css('color', '');
                }, 2000);
            });
        });
    </script>
</body>

</html>