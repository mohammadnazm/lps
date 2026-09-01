<?php
// The Excel Import/Export UI moved into the Archive page (arch.php), which now
// has both the Download button and the Import form in one place. This file is
// kept only so any old bookmark or link to /addexcel.php still works instead
// of showing a 404.
header('Location: arch.php');
exit;
