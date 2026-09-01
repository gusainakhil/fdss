<?php
$form_data = [
    'name' => '',
    'email' => '',
    'organisation' => '',
    'requirement' => '',
];

$form_message = '';
$form_state = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['organisation'] = trim($_POST['organisation'] ?? '');
    $form_data['requirement'] = trim($_POST['requirement'] ?? '');

    if (
        $form_data['name'] === '' ||
        $form_data['email'] === '' ||
        $form_data['organisation'] === '' ||
        $form_data['requirement'] === ''
    ) {
        $form_message = 'Please fill in all form fields before submitting.';
        $form_state = 'error';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $form_message = 'Please enter a valid email address.';
        $form_state = 'error';
    } else {
        $recipient = 'akhilgusain2@gmail.com';
        $safe_name = str_replace(["\r", "\n"], ' ', $form_data['name']);
        $safe_email = str_replace(["\r", "\n"], '', $form_data['email']);
        $server_name = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost.localdomain');

        if ($server_name === '') {
            $server_name = 'localhost.localdomain';
        }

        $subject = 'New FDSS / FSDS Demo Request - ' . $safe_name;
        $body = "A new enquiry was submitted from the FDSS / FSDS website.\n\n"
            . "Name: {$form_data['name']}\n"
            . "Email: {$form_data['email']}\n"
            . "Railway / Division / Unit: {$form_data['organisation']}\n"
            . "Requirement:\n{$form_data['requirement']}\n";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: FDSS Website <no-reply@' . $server_name . '>',
            'Reply-To: ' . $safe_name . ' <' . $safe_email . '>',
        ];

        if (mail($recipient, $subject, $body, implode("\r\n", $headers))) {
            $form_message = 'Thank you. Your details have been sent successfully.';
            $form_state = 'success';
            $form_data = [
                'name' => '',
                'email' => '',
                'organisation' => '',
                'requirement' => '',
            ];
        } else {
            $form_message = 'Mail sending failed on this server. Please check PHP mail configuration.';
            $form_state = 'error';
        }
    }
}

function website_value(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contact | FDSS / FSDS</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="nav">
  <div class="container nav-inner">
    <a class="brand" href="index.html"><img src="assets/beatle-logo.png" alt="Beatle Analytics"></a>
    <button class="menu-btn" aria-label="Open navigation" aria-expanded="false">☰</button>
    <nav class="nav-links">
      <a href="index.html">Home</a>
      <a href="about.html">About</a>
      <a href="how-it-works.html">How It Works</a>
      <a href="modules.html">Modules</a>
      <a class="active" href="contact.php">Contact</a>
      <a class="btn btn-primary btn-sm" href="login.php">Login</a>
    </nav>
  </div>
</header>

<section class="page-hero">
  <div class="container split">
    <div>
      <div class="eyebrow">Request a Demo</div>
      <h1>Bring Your FDSS / FSDS Workflow Online</h1>
      <p class="section-copy">Share your railway unit and operational requirement. On submit, this page sends the enquiry directly to the Beatle Analytics mailbox.</p>
    </div>
    <div class="visual-frame">
      <img src="assets/about-equipment.png" alt="Fire safety system equipment">
    </div>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div>
      <h2 class="section-title">Talk to Beatle Analytics</h2>
      <p class="section-copy">For product demonstration, deployment planning and module customisation.</p>
      <div class="check-list">
        <div class="check"><i>✓</i><span>FDSS / FSDS system monitoring</span></div>
        <div class="check"><i>✓</i><span>Inspection and auditor workflows</span></div>
        <div class="check"><i>✓</i><span>Warranty and component management</span></div>
        <div class="check"><i>✓</i><span>Reports, analytics and role-based access</span></div>
      </div>
    </div>

    <form class="login-card contact-card" method="POST" action="contact.php">
      <?php if ($form_message !== ''): ?>
        <div class="form-alert <?php echo $form_state; ?>"><?php echo website_value($form_message); ?></div>
      <?php endif; ?>

      <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" placeholder="Your name" value="<?php echo website_value($form_data['name']); ?>" required>
      </div>

      <div class="field">
        <label for="email">Official Email</label>
        <input id="email" name="email" type="email" placeholder="name@railnet.gov.in" value="<?php echo website_value($form_data['email']); ?>" required>
      </div>

      <div class="field">
        <label for="organisation">Railway / Division / Unit</label>
        <input id="organisation" name="organisation" type="text" placeholder="Organisation / Division" value="<?php echo website_value($form_data['organisation']); ?>" required>
      </div>

      <div class="field">
        <label for="requirement">Requirement</label>
        <textarea id="requirement" name="requirement" rows="5" placeholder="Tell us about your requirement" required><?php echo website_value($form_data['requirement']); ?></textarea>
      </div>

      <button class="btn btn-primary" type="submit">Request Demo</button>
    </form>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="copyright"><span>© 2026 Beatle Analytics Pvt. Ltd.</span><span>FDSS / FSDS</span></div>
  </div>
</footer>

<script src="js/main.js"></script>
</body>
</html>
