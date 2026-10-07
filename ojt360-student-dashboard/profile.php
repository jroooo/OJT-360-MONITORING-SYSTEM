<?php
$page_title='My Profile'; require_once 'includes/header.php'; $uid=(int)$user['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $full=trim($_POST['full_name']??'');$phone=trim($_POST['phone']??'');$address=trim($_POST['address']??'');
 if($full===''){flash('error','Full name is required.');}else{
  $imageName=$user['profile_image']??null;
  if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error']!==UPLOAD_ERR_NO_FILE){
   $f=$_FILES['profile_picture'];
   if($f['error']!==UPLOAD_ERR_OK || $f['size']>3*1024*1024){flash('error','Profile picture must be a valid image under 3 MB.');header('Location: profile.php');exit;}
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
   if(!isset($allowed[$mime])){flash('error','Only JPG, PNG, or WEBP profile pictures are allowed.');header('Location: profile.php');exit;}
   $imageName='student_'.$uid.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime]; $dir=__DIR__.'/uploads/profile'; if(!is_dir($dir))mkdir($dir,0755,true);
   if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$imageName)){flash('error','The profile picture could not be saved.');header('Location: profile.php');exit;}
  }
  db()->prepare('UPDATE users SET full_name=?,phone=?,address=?,profile_image=? WHERE id=?')->execute([$full,$phone,$address,$imageName,$uid]);
  db()->prepare('INSERT INTO activity_logs(user_id,action_text) VALUES(?,?)')->execute([$uid,'Updated profile information']);flash('success','Profile saved successfully.');
 }
 header('Location: profile.php');exit;
}
$intern=db()->prepare('SELECT i.*,c.name company_name FROM internships i JOIN companies c ON c.id=i.company_id WHERE i.user_id=? ORDER BY i.id DESC LIMIT 1');$intern->execute([$uid]);$i=$intern->fetch();$img=''; if(!empty($user['profile_image'])){ $imgFile=__DIR__.'/uploads/profile/'.basename($user['profile_image']); if(is_file($imgFile)) $img='uploads/profile/'.basename($user['profile_image']).'?v='.filemtime($imgFile); }
?>
<div class="page-intro"><div><span class="eyebrow">ACCOUNT</span><h2>My Profile</h2><p>Keep your student information accurate for OJT records.</p></div></div>
<div class="profile-grid"><section class="panel profile-card"><div class="profile-hero"><div class="profile-avatar-wrap profile-avatar-action"><button type="button" class="profile-page-avatar-button" id="profilePageAvatarButton" aria-label="Profile picture options" aria-haspopup="true" aria-expanded="false"><div class="avatar large"><?php if($img):?><img src="<?=e($img)?>" alt="Profile picture"><?php else:?><?=e(initials($user['full_name']))?><?php endif;?></div><span class="profile-photo-camera" aria-hidden="true">✎</span></button><span class="profile-status">● Active</span><div class="profile-page-menu" id="profilePageMenu" role="menu"><button type="button" id="profilePageView" role="menuitem">View Profile <span>›</span></button><button type="button" id="profilePageUpload" role="menuitem">Upload Profile <span>↑</span></button><button type="button" id="profilePageDelete" class="profile-delete" role="menuitem">Delete Profile <span>×</span></button></div><input type="file" id="profilePageInput" accept="image/jpeg,image/png,image/webp" hidden></div><div><h3><?=e($user['full_name'])?></h3><span><?=e($user['student_no'])?></span></div></div><div class="info-list"><div><span>Email</span><strong><?=e($user['email'])?></strong></div><div><span>Course</span><strong><?=e($user['course'])?></strong></div><div><span>Section</span><strong><?=e($user['section'])?></strong></div><div><span>Company</span><strong><?=e($i['company_name']??'Not assigned')?></strong></div></div></section>
<section class="panel"><div class="panel-head"><div><span class="eyebrow">PERSONAL DETAILS</span><h3>Edit Information</h3></div></div><form method="post" class="form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Full name<input name="full_name" value="<?=e($user['full_name'])?>" required></label><label>Email<input value="<?=e($user['email'])?>" disabled></label><div class="form-row"><label>Phone<input name="phone" value="<?=e($user['phone'])?>"></label><label>Student No.<input value="<?=e($user['student_no'])?>" disabled></label></div><label>Address<textarea name="address" rows="4"><?=e($user['address'])?></textarea></label><button class="btn primary">Save Changes</button></form></section></div>
<?php require_once 'includes/footer.php';?>
