<?php

foreach ( array('DB_USER', 'DB_PASS', 'DB_HOST', 'DB_NAME') as $const ) {
  define($const, getenv($const));
}

mysql_connect(DB_HOST, DB_USER, DB_PASS) or die("Failed to connect to database.");
mysql_select_db(DB_NAME) or die("Failed to select database.");

// IPN notification
if ( isset($_POST['item_name']) && $_POST['item_name'] == 'One Laptop Per Lertch Contribution' ) {
  
  error_log("Received IPN: {$_POST['item_name']} ({$_POST['payment_gross']}) from {$_POST['payer_email']}");
  
  // validate notification with paypal
  $url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_notify-validate&'.http_build_query($_POST);
  $result = file_get_contents($url);
  
  // if payment looks good, log it
  if ( $result == 'VERIFIED' && $_POST['payment_status'] == 'Completed' ) {
    $sql = 'INSERT INTO donations (name, email, amount, paypal_txn_id)
            VALUES (
              "' . mysql_real_escape_string($_POST['first_name'].' '.$_POST['last_name']) . '",
              "' . mysql_real_escape_string($_POST['payer_email']) . '",
              ' . (double)$_POST['payment_gross'] . ',
              "' . mysql_real_escape_string($_POST['txn_id']) . '"
            )';
    
    if ( !mysql_query($sql) ) {
      error_log("Failed to log donation: " . mysql_error());
    }
  }
  
  die('Thanks!');
}
// User clicked the "return" button at Paypal
elseif ( isset($_GET['txn_id']) ) {
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

$goal = 400;
$total = 0;

$sql = 'SELECT SUM(amount) FROM donations';
$result = mysql_query($sql);

if ( mysql_num_rows($result) ) {
  list($total) = mysql_fetch_array($result);
}

?>

<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
  <title>The One Laptop Per Lertch Project</title>
  <script type="text/javascript" src="http://37i.net/jquery.js"></script>
  <link rel="stylesheet" type="text/css" href="http://37i.net/bootstrap.css" />
  <style type="text/css">
  #wrapper {
    padding-top: 25px;
    max-width: 100%;
  }
  #photo {
    position: relative;
  }
  #photo img {
    border-radius: 6px 6px 0 0;
  }
  #photo #progress {
    background-color: rgba(0, 0, 0, 0.5);
    padding: 18px 24px 6px;
    position: absolute;
    bottom: 4px;
    left: 0;
    border-radius: 0 6px 0 0;
  }
  #progress h1 {
    color: #fff;
    text-shadow: 1px 1px 0 #000;
    margin-bottom: 5px;
  }
  .hero-unit {
    margin-top: -6px;
    border-radius: 0 0 6px 6px;
    padding-top: 40px;
  }
  .hero-unit p {
    font-weight: normal;
    margin: 12px 0;
  }
  .hero-unit p strong {
    font-size: 1.2em;
  }  
  .hero-unit form {
    margin: 32px 0 0 0;
  }
  .hero-unit .btn-large {
    font-size: 20px;
  }
  </style>
</head>
<body>
  <div id="wrapper" class="container">
    <div id="photo">
      <img src="images/olpl.jpg" alt="Mr. Lertch" />
      <div id="progress">
        <h1>$<?php echo number_format($total, 2) ?> raised so far.</h1>
        <div class="progress progress-info progress-striped active">
          <div class="bar" style="width: <?php echo number_format(($total / $goal) * 100, 2) ?>%;"></div>
        </div>
      </div>
    </div>
    <div class="hero-unit">
      <h1>One Laptop Per Lertch</h1>
      <p>As I understand it, there are two things missing from John Lertch's life: an automobile and a personal computer. He's on his own for the car, but if we each kick in like 10 bucks, we can probably get him a new computer for his <a href="https://www.facebook.com/events/263877050376018/">30th birthday</a>.</p>

      <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_donations">
        <input type="hidden" name="business" value="ken@37i.net">
        <input type="hidden" name="lc" value="US">
        <input type="hidden" name="item_name" value="One Laptop Per Lertch Contribution">
        <input type="hidden" name="no_note" value="0">
        <input type="hidden" name="currency_code" value="USD">
        <input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
        <input type="hidden" name="return" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
        <button class="btn btn-primary btn-large">Contribute</button>
      </form>
    </div>
  </div>
</body>
</html>
  