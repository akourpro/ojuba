<?php function h($v)
{
  return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
} ?>
<?php
$id = numer($_GET['id'] ?? 0);
dbSelect("faq", "*", "WHERE id = ? LIMIT 1", [$id]);
if ($countrows === 0) {
  sweet("error", "خطأ", "السجل غير موجود", "faq");
  exit;
}
$row = $rows[0];

if (isset($_POST['submit'])) {
  $csrf->verify();
  $question = safer($_POST['question'] ?? null);
  $question_en = safer($_POST['question_en'] ?? null);
  $answer = $_POST['answer'] ?? null;
  $answer_en = $_POST['answer_en'] ?? null;
  $ordering = numer($_POST['ordering'] ?? 0);
  $status = safer($_POST['status'] ?? null);

  $columns = "question = ?, question_en = ?, answer = ?, answer_en = ?, ordering = ?, status = ?";
  $values = [$question, $question_en, $answer, $answer_en, $ordering, $status, $id];
  dbUpdate("faq", $columns, $values, "WHERE id = ? LIMIT 1");
  sweet("success", "تم", "تم التحديث بنجاح", "faq");
  exit;
}
?>
<title>تعديل سؤال</title>
<div class="card mb-4">
  <h5 class="card-header">تعديل</h5>
  <form class="card-body" method="post">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="question" value="<?php echo h($row['question']) ?>">
          <label>السؤال (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="text" class="form-control" name="question_en" value="<?php echo h($row['question_en']) ?>">
          <label>السؤال (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="answer" style="height:120px"><?php echo h($row['answer']) ?></textarea>
          <label>الجواب (عربي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" name="answer_en" style="height:120px"><?php echo h($row['answer_en']) ?></textarea>
          <label>الجواب (انجليزي)</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <input type="number" class="form-control" name="ordering" value="<?php echo h($row['ordering']) ?>">
          <label>ترتيب العرض</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-floating form-floating-outline">
          <select class="form-select" name="status"><?php $__cur = ($row['status'] ?? null); ?>
            <option value="active" <?php if ((string)$__cur === 'active') echo 'selected'; ?>>ظاهر</option>
            <option value="disabled" <?php if ((string)$__cur === 'disabled') echo 'selected'; ?>>مخفي</option>
          </select><label>الحالة</label>
        </div>
      </div>

    </div>
    <div class="pt-4">
      <?php $csrf->input(); ?>
      <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-pen"></i> تحديث</button>
    </div>
  </form>
</div>

<script src="js/faq.js"></script>
