<?php
$target_dir = '/var/www/html/upload/';
$max_width = 800;

date_default_timezone_set('America/New_York');

// This function returns a misleading message in the event of failure.
// It's presumed that you will look at the PHP logs if you see "Success!"
//
// TODO: find a better way to secure this.
function done( $success = false ) {
  if ( false === $success ) {
    echo 'Success!';
    exit;
  } else {
    echo $success;
    exit;
  }
}

// this script only handles POSTs.
// And make sure that our "secret" token is correct.
if ( ( ! isset( $_POST ) ) || ( $_POST['token'] != 'CHANGEME' ) ) {
  done();
}

if ( empty($_POST['title']) ) { done(); }
if ( empty( $_FILES['image'] ) ) { done(); }

$title = $_POST['title'];
$slug = strtolower( preg_replace("/[^-\w+]/", "", str_replace(' ', '-', $title) ) );
$description = $_POST['description'];
$content = $_POST['content'];

// ensure or set up our upload directory
if (! file_exists($target_dir) ) {
  if ( !  mkdir($target_dir) ) {
    // give a legit error here.
    done ('An error occurred.');
  }
}

$ext = strtolower( pathinfo(basename($_FILES['image']['name']), PATHINFO_EXTENSION) );
// don't futz with the image name: just rename the image to use the
// current time.  once it's been uploaded, no one should care about
// it's name.
$target_file = $target_dir . date('YmdHis') . ".$ext";
$image_link = 'https://skippy.is/images/' . basename($target_file);

// Check if file already exists
// and check file size
if ( (file_exists($target_file)) || ($_FILES['image']['size'] > 5242880 ) ) {
  done();
}

// DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
// Check MIME Type by yourself.
$finfo = new finfo(FILEINFO_MIME_TYPE);
if (false === $ext = array_search(
  $finfo->file($_FILES['image']['tmp_name']),
  array(
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
  ),
  true
)) {
  done();
}

// if everything is ok, try to upload file
if (! move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
  done();
}
// now try to reduce the image size, if necessary
switch( $ext ) {
  case 'jpg':
  case 'jpeg':
    $im = imagecreatefromjpeg($target_file);
    break;
  case 'gif':
    $im = imagecreatefromgif($target_file);
    break;
  case 'png':
    $im = imagecreatefrompng($target_file);
  default:
    # somehow we got an invalid file extension
    done();
}
if ( ! $im ) {
  //we didn't get an image resource, for some reason
  done();
}

if ( imagesx( $im ) > $max_width ) {
  $im = imagescale( $im, $max_width );
}
switch( $ext ) {
  case 'jpg':
  case 'jpeg':
    $result = imagejpeg( $im, $target_file);
    break;
  case 'gif':
    $result = imagegif( $im, $target_file );
    break;
  case 'png':
    $result = imagepng( $im, $target_file );
  default:
    # somehow we got an invalid file extension?
    done();
}
if ( ! $result ) {
  // unable to create the file for some reason
  done();
}
// Free up memory
imagedestroy($im);
chmod($target_file, 0777);

// Build up the post file
$post = "---\n";
$post .= "title: $title \n";
$post .= "description: $description\n";
$post .= 'date: ' . date('Y-m-d H:i:s') . "\n";
$post .= "permalink: $slug\n";
$post .= "twitterimage: $image_link\n";
$post .= "---\n";
$post .= "![skippy is $title]($image_link)\n\n";
$post .= "$content\n";

// use the slug for the filename.
// WE DO NOT CHECK FOR DUPLICATES HERE. :(
$file = $target_dir . $slug . '.md';
if ( ! $fh = fopen( $file, 'w' ) ) {
  done();
}
if ( fwrite($fh, $post ) === FALSE ) {
  done();
}
fclose($fh);
chmod($file, 0777);
// show the user that things worked out.
echo "<pre>$post</pre>";
?>
