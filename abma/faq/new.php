<?php function h($v)
{
  return htmlspecialchars($v ?? null, ENT_QUOTES, 'UTF-8');
} ?>
<title>اضافة سؤال جديد</title>
<?php
$status = '';
if (isset($_POST['submit'])) {
  $csrf->verify();
  $question = safer($_POST['question'] ?? null);
  $question_en = safer($_POST['question_en'] ?? null);
  $answer = $_POST['answer'] ?? null;
  $answer_en = $_POST['answer_en'] ?? null;
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $columns = "question, question_en, answer, answer_en, ordering, status, date";
  $values = [$question, $question_en, $answer, $answer_en, $ordering, $status, date('Y-m-d H:i:s')];
  dbInsert("faq", $columns, $values);
  sweet("success", "تم", "تمت الإضافة بنجاح", "faq");
  exit;
}
?>
<div class="card mb-4">
  <h5 class="card-header">إضافة جديد</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="question" value="<?php echo $question ?? '' ?>" placeholder="السؤال" required>
          <label>السؤال (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="question_en" value="<?php echo $question_en ?? '' ?>" placeholder="السؤال (انجليزي)">
          <label>السؤال (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="answer" placeholder="الجواب" style="height:120px" required><?php echo $answer ?? '' ?></textarea>
          <label>الجواب (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="answer_en" placeholder="الجواب (انجليزي)" style="height:120px"><?php echo $answer_en ?? '' ?></textarea>
          <label>الجواب (انجليزي) <sup class="text-success">(اختياري)</sup></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="0">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status">
            <option value="active" <?php if ($status == "active") echo "selected" ?>>ظاهر</option>
            <option value="disabled" <?php if ($status == "disabled") echo "selected" ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-plus"></i> حفظ</button>
    </div>
  </form>
</div>

<script src="js/faq.js"></script>
