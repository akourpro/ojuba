<?php
// بوتستراب صريح بدل الاعتماد على auto_prepend_file (بعض الاستضافات لا تدعمه
// إطلاقاً — راجع تعليق abma/autoload.php لتفاصيل كاملة). آمن حتى لو نجح
// auto_prepend_file أيضاً على استضافات أخرى، لأن require_once يتجاهل أي
// تحميل مكرَّر لنفس الملف تلقائياً.
$__d = __DIR__;
while (!is_file($__d . '/autoload.php') && $__d !== dirname($__d)) {
    $__d = dirname($__d);
}
require_once $__d . '/autoload.php';
?>
<?php
requireOwner();
$activeTheme = $site['theme'];
$themeBase = realpath(getpath() . 'templates/' . $activeTheme);

$editableExt = ['twig', 'css', 'js', 'json', 'svg', 'txt', 'md'];

function te_icon_for($name, $isDir)
{
    if ($isDir) return 'mdi-folder-outline';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'twig':
            return 'mdi-file-code-outline';
        case 'css':
            return 'mdi-language-css3';
        case 'js':
            return 'mdi-language-javascript';
        case 'json':
            return 'mdi-code-json';
        case 'png':
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'webp':
        case 'svg':
            return 'mdi-file-image-outline';
        case 'ttf':
        case 'woff':
        case 'woff2':
        case 'eot':
            return 'mdi-format-font';
        default:
            return 'mdi-file-outline';
    }
}

function te_render_tree($dir, $relBase, $editableExt)
{
    $items = scandir($dir);
    $folders = [];
    $files = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item[0] === '.') continue;
        $full = $dir . '/' . $item;
        if (is_dir($full)) {
            $folders[] = $item;
        } else {
            $files[] = $item;
        }
    }
    natcasesort($folders);
    natcasesort($files);

    echo '<ul class="te-tree">';
    foreach ($folders as $item) {
        $rel = ltrim($relBase . '/' . $item, '/');
        echo '<li class="te-node te-folder">';
        echo '<span class="te-row" data-type="folder" data-path="' . htmlspecialchars($rel, ENT_QUOTES) . '">';
        echo '<i class="mdi mdi-chevron-left te-toggle"></i>';
        echo '<i class="mdi ' . te_icon_for($item, true) . '"></i>';
        echo '<span class="te-name">' . htmlspecialchars($item, ENT_QUOTES) . '</span>';
        echo '</span>';
        te_render_tree($dir . '/' . $item, $rel, $editableExt);
        echo '</li>';
    }
    foreach ($files as $item) {
        $rel = ltrim($relBase . '/' . $item, '/');
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        $editable = in_array($ext, $editableExt, true) ? '1' : '0';
        echo '<li class="te-node te-file' . ($editable === '0' ? ' te-readonly' : '') . '">';
        echo '<span class="te-row" data-type="file" data-editable="' . $editable . '" data-path="' . htmlspecialchars($rel, ENT_QUOTES) . '">';
        echo '<i class="mdi ' . te_icon_for($item, false) . '"></i>';
        echo '<span class="te-name">' . htmlspecialchars($item, ENT_QUOTES) . '</span>';
        echo '</span>';
        echo '</li>';
    }
    echo '</ul>';
}
?>
<title>تحرير القالب</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<style>
    .te-wrap {
        display: flex;
        gap: 16px;
        align-items: stretch;
    }

    .te-sidebar {
        width: 300px;
        flex-shrink: 0;
        background: #fff;
        border: 1px solid #dbdade;
        border-radius: 8px;
        padding: 12px;
        max-height: 78vh;
        overflow: auto;
    }

    .te-sidebar-actions {
        display: flex;
        gap: 6px;
        margin-bottom: 10px;
    }

    .te-tree,
    .te-tree ul {
        list-style: none;
        margin: 0;
        padding-inline-start: 18px;
    }

    .te-sidebar>.te-tree {
        padding-inline-start: 0;
    }

    .te-row {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 5px 6px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13.5px;
        white-space: nowrap;
    }

    .te-row:hover {
        background: #f1f0f5;
    }

    .te-row.active {
        background: #696cff;
        color: #fff;
    }

    .te-readonly .te-row {
        opacity: .55;
        cursor: not-allowed;
    }

    .te-folder>ul {
        display: none;
    }

    .te-folder.open>ul {
        display: block;
    }

    .te-folder.open>.te-row .te-toggle {
        transform: rotate(-90deg);
    }

    .te-toggle {
        transition: transform .15s ease;
        font-size: 16px;
    }

    .te-editor-panel {
        flex: 1;
        background: #fff;
        border: 1px solid #dbdade;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .te-editor-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid #dbdade;
        gap: 10px;
    }

    .te-current-path {
        font-family: monospace;
        font-size: 13px;
        color: #566a7f;
    }

    .CodeMirror {
        flex: 1;
        height: 70vh !important;
        font-size: 13.5px;
        direction: ltr;
        text-align: left;
    }

    /* في حال تعذّر تحميل CodeMirror (لا اتصال بالإنترنت) تُستخدم هذه كـ fallback */
    textarea#teTextarea {
        flex: 1;
        height: 70vh;
        width: 100%;
        border: none;
        padding: 16px;
        font-family: monospace;
        font-size: 13.5px;
        direction: ltr;
        text-align: left;
        resize: none;
        outline: none;
    }

    .te-empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 70vh;
        color: #a1acb8;
        flex-direction: column;
        gap: 10px;
    }
</style>

<h4 class="py-3 mb-3"><span class="text-muted fw-light">القوالب /</span> تحرير القالب المفعّل: <?php echo htmlspecialchars($activeTheme, ENT_QUOTES) ?></h4>

<div class="alert alert-warning">
    التعديل هنا مباشر على ملفات القالب المفعّل حالياً (<code>templates/<?php echo htmlspecialchars($activeTheme, ENT_QUOTES) ?></code>).
    يُسمح فقط بتحرير ملفات: twig, css, js, json, svg, txt, md — لا يمكن رفع أو تعديل أكواد PHP من هنا لأسباب أمنية.
    يُفضّل أخذ نسخة احتياطية من مجلد القالب قبل التعديل.
</div>

<div class="te-wrap">
    <div class="te-sidebar">
        <div class="te-sidebar-actions">
            <button class="btn btn-sm btn-outline-primary" id="teNewFile"><i class="mdi mdi-file-plus-outline"></i> ملف</button>
            <button class="btn btn-sm btn-outline-secondary" id="teNewFolder"><i class="mdi mdi-folder-plus-outline"></i> مجلد</button>
        </div>
        <?php if ($themeBase): te_render_tree($themeBase, '', $editableExt); else: ?>
            <p class="text-danger">تعذّر العثور على مجلد القالب.</p>
        <?php endif; ?>
    </div>

    <div class="te-editor-panel">
        <div class="te-editor-toolbar">
            <span class="te-current-path" id="teCurrentPath">لم يتم اختيار ملف بعد</span>
            <div>
                <button class="btn btn-sm btn-outline-danger" id="teDeleteBtn" style="display:none"><i class="mdi mdi-delete-outline"></i> حذف</button>
                <button class="btn btn-sm btn-primary" id="teSaveBtn" style="display:none"><i class="mdi mdi-content-save-outline"></i> حفظ</button>
            </div>
        </div>
        <div class="te-empty-state" id="teEmptyState">
            <i class="mdi mdi-file-code-outline" style="font-size:48px"></i>
            <p>اختر ملفاً من القائمة الجانبية للبدء بالتعديل</p>
        </div>
        <textarea id="teTextarea" style="display:none"></textarea>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="js/theme-editor.js"></script>
