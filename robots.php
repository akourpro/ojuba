<?php
header("Content-Type: text/plain");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
?>

User-agent: *
Disallow: /admin/

# Allow everything else
Allow: /

# Sitemap
Sitemap: <?php echo $site['site_url'] ?>sitemap.xml