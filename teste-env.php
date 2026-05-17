<?php
echo getenv('CLOUDINARY_CLOUD_NAME') ?: 'sem_cloud_name';
echo "<br>";
echo getenv('CLOUDINARY_UPLOAD_PRESET') ?: 'sem_preset';