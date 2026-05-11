<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: ../pages/dashboard.php"); exit; }
require_once "../config/db.php";

$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $phone  = trim($_POST['phone']  ?? '');
    $org    = trim($_POST['organisation'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $age    = trim($_POST['age']    ?? '');
    $pass   = $_POST['password']    ?? '';
    $conf   = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors['name'] = 'Full name is required.';
    elseif (!preg_match('/^[A-Za-z\s\'\-\.]{2,80}$/', $name)) $errors['name'] = 'Name may only contain letters, spaces, hyphens or apostrophes (2–80 chars).';

    if ($email === '') $errors['email'] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email (e.g. surveyor@example.com).';

    if ($phone !== '') {
        $d = preg_replace('/[\s\-\+\(\)]/', '', $phone);
        if (!preg_match('/^[6-9]\d{9}$/', $d)) $errors['phone'] = 'Enter a valid 10-digit Indian mobile number starting with 6–9.';
    }

    $allowedG = ['Male','Female','Non-binary','Prefer not to say'];
    if (!in_array($gender, $allowedG)) $errors['gender'] = 'Please select your gender.';

    if ($age === '') $errors['age'] = 'Age is required.';
    elseif (!ctype_digit($age) || (int)$age < 16 || (int)$age > 80) $errors['age'] = 'Age must be between 16 and 80.';

    if ($org !== '' && strlen($org) > 120) $errors['organisation'] = 'Organisation name must be under 120 characters.';

    if (strlen($pass) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) $errors['password'] = 'Password must contain at least one letter and one number.';

    if ($pass !== $conf) $errors['confirm_password'] = 'Passwords do not match.';

    if (empty($errors)) {
        $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $st->execute([$email]);
        if ($st->fetch()) $errors['email'] = 'An account with this email already exists.';
    }

    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name,email,phone,organisation,gender,age,password,created_at) VALUES (?,?,?,?,?,?,?,NOW())")
            ->execute([$name, $email, $phone ?: null, $org ?: null, $gender, (int)$age, $hash]);
        $success = true;
    }
}

function fe($k, $e) { return isset($e[$k]) ? '<div class="ferr">'.htmlspecialchars($e[$k]).'</div>' : ''; }
function fc($k, $e) { return isset($e[$k]) ? ' err' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — CycleAudit</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--g:#3d7a1f;--gl:#86B93B;--gp:#edf7d6;--gd:#1e3d0d;--cream:#f7f4ee;--ink:#181f10;--gray:#5e6b54;--red:#dc2626;--redp:#fef2f2;--bd:rgba(61,122,31,.15);--r:10px;--T:.22s ease}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--cream);min-height:100vh;display:grid;grid-template-columns:400px 1fr}

/* LEFT PANEL */
.lp{background:linear-gradient(160deg,var(--gd),#0f2206);color:#fff;display:flex;flex-direction:column;padding:44px 40px;position:sticky;top:0;height:100vh;overflow:hidden}
.lp::before{content:'';position:absolute;top:-80px;right:-80px;width:300px;height:300px;border-radius:50%;background:rgba(134,185,59,.1);pointer-events:none}
.brand{display:flex;align-items:center;gap:10px;margin-bottom:48px;position:relative;z-index:1}
.bm{width:38px;height:38px;border-radius:10px;background:var(--gl);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.bm svg{width:20px;height:20px;fill:#fff}
.bn{font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700}
.lp-h{font-family:'Playfair Display',serif;font-size:1.75rem;line-height:1.2;font-weight:800;margin-bottom:14px;position:relative;z-index:1}
.lp-h span{color:var(--gl)}
.lp-s{font-size:.85rem;color:rgba(255,255,255,.65);line-height:1.7;margin-bottom:40px;position:relative;z-index:1}
.pills{display:flex;flex-direction:column;gap:11px;position:relative;z-index:1}
.pill{display:flex;align-items:flex-start;gap:12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);border-radius:11px;padding:13px 15px}
.pill-i{font-size:1.1rem;flex-shrink:0;margin-top:1px}
.pill strong{display:block;font-size:.85rem;font-weight:600;margin-bottom:2px}
.pill span{font-size:.76rem;color:rgba(255,255,255,.55)}
.lp-ft{margin-top:auto;padding-top:20px;border-top:1px solid rgba(255,255,255,.08);font-size:.74rem;color:rgba(255,255,255,.35);position:relative;z-index:1}

/* RIGHT PANEL */
.rp{padding:36px 48px;overflow-y:auto;display:flex;align-items:flex-start;justify-content:center}
.fw{width:100%;max-width:540px;padding:6px 0}
.ft{font-family:'Playfair Display',serif;font-size:1.65rem;font-weight:800;color:var(--ink);margin-bottom:4px}
.fs{font-size:.85rem;color:var(--gray);margin-bottom:26px}
.fs a{color:var(--g);font-weight:600;text-decoration:none}
.fs a:hover{text-decoration:underline}

/* success */
.sbox{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--r);padding:28px;text-align:center}
.sbox .si{font-size:2.6rem;margin-bottom:10px}
.sbox h3{font-family:'Playfair Display',serif;font-size:1.25rem;color:#15803d;margin-bottom:6px}
.sbox p{font-size:.85rem;color:#166534;line-height:1.6}
.sbox a{display:inline-block;margin-top:16px;background:var(--g);color:#fff;padding:10px 26px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.88rem}

/* section divider */
.sdiv{font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--g);margin:22px 0 14px;display:flex;align-items:center;gap:8px}
.sdiv::before,.sdiv::after{content:'';flex:1;height:1px;background:var(--bd)}

/* form groups */
.fg{margin-bottom:14px}
.fg label{display:block;font-size:.8rem;font-weight:600;color:var(--ink);margin-bottom:5px}
.fg label .req{color:var(--red);margin-left:2px}
.fg label .hint{font-size:.7rem;color:#a0b090;font-weight:400;margin-left:5px}
.r2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* inputs — NO padding-left icon, icon is label prefix only */
input[type=text],input[type=email],input[type=tel],input[type=number],input[type=password],select{
  width:100%;padding:10px 14px;border:1.5px solid #cdddb8;border-radius:9px;
  font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--ink);
  background:#fff;outline:none;transition:border-color var(--T),box-shadow var(--T);
  appearance:none;-webkit-appearance:none;
}
input:focus,select:focus{border-color:var(--gl);box-shadow:0 0 0 3px rgba(134,185,59,.18)}
.fg.err input,.fg.err select{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,38,.1)}
.ferr{font-size:.72rem;color:var(--red);margin-top:4px}

/* password row with eye */
.pw-wrap{position:relative}
.pw-wrap input{padding-right:60px}
.eye-btn{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9aaa88;font-size:.76rem;font-weight:700;font-family:'DM Sans',sans-serif;padding:4px 6px}
.eye-btn:hover{color:var(--g)}

/* strength */
.str-wrap{margin-top:6px}
.str-bar{height:4px;border-radius:4px;background:#e0ecd4;overflow:hidden}
.str-fill{height:100%;width:0;border-radius:4px;transition:width .35s,background .35s}
.str-lbl{font-size:.7rem;color:#a0b090;margin-top:3px}

/* gender chips */
.gc{display:flex;gap:7px;flex-wrap:wrap}
.gc input[type=radio]{display:none}
.gc label{padding:7px 14px;border-radius:100px;border:1.5px solid #cdddb8;font-size:.8rem;font-weight:600;color:var(--gray);cursor:pointer;transition:all var(--T);background:#fff;user-select:none}
.gc input[type=radio]:checked+label{background:var(--gp);border-color:var(--gl);color:var(--gd)}
.gc label:hover{border-color:var(--gl);background:var(--gp)}
.fg.err .gc label{border-color:rgba(220,38,38,.4)}

/* submit */
.sub-btn{width:100%;padding:12px;background:var(--g);color:#fff;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.94rem;font-weight:700;cursor:pointer;margin-top:6px;transition:background var(--T),transform var(--T);box-shadow:0 3px 12px rgba(61,122,31,.28)}
.sub-btn:hover{background:var(--gd);transform:translateY(-1px)}
.back{text-align:center;margin-top:18px;font-size:.8rem;color:var(--gray)}
.back a{color:var(--g);font-weight:600;text-decoration:none}

@media(max-width:800px){body{grid-template-columns:1fr}.lp{display:none}.rp{padding:28px 20px}}
</style>
</head>
<body>

<div class="lp">
  <div class="brand">
    <div class="bm"><svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.3 7 13 7 13s7-7.7 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg></div>
    <span class="bn">CycleAudit</span>
  </div>
  <div class="lp-h">Join the<br><span>Audit Network.</span></div>
  <p class="lp-s">Register as a surveyor and contribute to Pune's cycle infrastructure dataset. Your field data drives Parisar's advocacy with municipal bodies.</p>
  <div class="pills">
    <div class="pill"><div class="pill-i">🗺️</div><div><strong>Geo-Referenced Segments</strong><span>Each audit linked to precise road segments with GPS</span></div></div>
    <div class="pill"><div class="pill-i">📊</div><div><strong>Multi-Dimensional Scoring</strong><span>Safety, Continuity and Comfort from your field notes</span></div></div>
    <div class="pill"><div class="pill-i">📄</div><div><strong>Professional PDF Reports</strong><span>Auto-generate stakeholder reports from submissions</span></div></div>
    <div class="pill"><div class="pill-i">🌱</div><div><strong>Drive Real Impact</strong><span>Parisar uses your data to advocate for better cycling</span></div></div>
  </div>
  <div class="lp-ft">A Parisar initiative — parisar.org</div>
</div>

<div class="rp">
  <div class="fw">
    <div class="ft">Create Account</div>
    <p class="fs">Already have an account? <a href="login.php">Sign in here</a></p>

    <?php if ($success): ?>
    <div class="sbox">
      <div class="si">✅</div>
      <h3>Registration Successful!</h3>
      <p>Your surveyor account has been created. You can now log in and start auditing cycle tracks across Pune.</p>
      <a href="login.php">Sign In Now →</a>
    </div>

    <?php else: ?>
    <form method="POST" novalidate id="rf">

      <div class="sdiv">Personal Information</div>

      <div class="fg<?= fc('name',$errors) ?>">
        <label>Full Name <span class="req">*</span> <span class="hint">letters only, 2–80 chars</span></label>
        <input type="text" name="name" id="inp-name" placeholder="Enter your full name" maxlength="80" autocomplete="off">
        <?= fe('name',$errors) ?>
      </div>

      <div class="r2">
        <div class="fg<?= fc('age',$errors) ?>">
          <label>Age <span class="req">*</span> <span class="hint">16–80</span></label>
          <input type="number" name="age" id="inp-age" placeholder="Your age" min="16" max="80">
          <?= fe('age',$errors) ?>
        </div>
        <div class="fg<?= fc('gender',$errors) ?>">
          <label>Gender <span class="req">*</span></label>
          <select name="gender" id="inp-gender">
            <option value="">— Select gender —</option>
            <option>Male</option>
            <option>Female</option>
            <option>Non-binary</option>
            <option>Prefer not to say</option>
          </select>
          <?= fe('gender',$errors) ?>
        </div>
      </div>

      <div class="sdiv">Contact Details</div>

      <div class="fg<?= fc('email',$errors) ?>">
        <label>Email Address <span class="req">*</span></label>
        <input type="email" name="email" id="inp-email" placeholder="Enter your email address" autocomplete="off">
        <?= fe('email',$errors) ?>
      </div>

      <div class="r2">
        <div class="fg<?= fc('phone',$errors) ?>">
          <label>Mobile Number <span class="hint">10-digit Indian</span></label>
          <input type="tel" name="phone" id="inp-phone" placeholder="9XXXXXXXXX" maxlength="10">
          <?= fe('phone',$errors) ?>
        </div>
        <div class="fg<?= fc('organisation',$errors) ?>">
          <label>Organisation <span class="hint">optional</span></label>
          <input type="text" name="organisation" id="inp-org" placeholder="Your institute or NGO" maxlength="120">
          <?= fe('organisation',$errors) ?>
        </div>
      </div>

      <div class="sdiv">Set Password</div>

      <div class="fg<?= fc('password',$errors) ?>">
        <label>Password <span class="req">*</span> <span class="hint">min 8 chars, 1 letter + 1 number</span></label>
        <div class="pw-wrap">
          <input type="password" name="password" id="inp-pass" placeholder="Create a strong password" oninput="checkStr(this.value)">
          <button type="button" class="eye-btn" onclick="tog('inp-pass',this)">Show</button>
        </div>
        <div class="str-wrap">
          <div class="str-bar"><div class="str-fill" id="sf"></div></div>
          <div class="str-lbl" id="sl">Enter a password</div>
        </div>
        <?= fe('password',$errors) ?>
      </div>

      <div class="fg<?= fc('confirm_password',$errors) ?>">
        <label>Confirm Password <span class="req">*</span></label>
        <div class="pw-wrap">
          <input type="password" name="confirm_password" id="inp-conf" placeholder="Re-enter your password">
          <button type="button" class="eye-btn" onclick="tog('inp-conf',this)">Show</button>
        </div>
        <?= fe('confirm_password',$errors) ?>
      </div>

      <button type="submit" class="sub-btn">Create Surveyor Account →</button>
    </form>
    <?php endif; ?>
    <div class="back"><a href="../index.html">← Back to Home</a></div>
  </div>
</div>

<script>
function tog(id, btn) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
  btn.textContent = el.type === 'password' ? 'Show' : 'Hide';
}
function checkStr(v) {
  let s=0;
  if(v.length>=8)s++;if(v.length>=12)s++;
  if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const c=['','#dc2626','#f97316','#eab308','#84cc16','#16a34a'];
  const w=['0%','22%','42%','62%','82%','100%'];
  const l=['','Weak','Fair','Fair','Good','Strong'];
  document.getElementById('sf').style.cssText='width:'+w[s]+';background:'+c[s];
  document.getElementById('sl').textContent = v.length?(l[s]||''):'Enter a password';
}
// phone: digits only
document.getElementById('inp-phone').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').slice(0,10)});
// name: letters only
document.getElementById('inp-name').addEventListener('input',function(){this.value=this.value.replace(/[^A-Za-z\s'\-\.]/g,'')});
// age: clamp
document.getElementById('inp-age').addEventListener('input',function(){if(+this.value>80)this.value=80;if(this.value<0)this.value=''});
</script>
</body>
</html>