<?php
// Database configuration
session_start();
include 'db/db_connection.php';
// Create a connection to the database
$conn = sqlsrv_connect($serverName, $connectionOptions);


if (!$conn) {
  header("Location: error.html");
  //$_SESSION['message'] = ['type' => 'error', 'text' => 'A connection error occured. Please try again later!'];
}
$evname = $EvID = $EvDetails = '';
$errors = array(
  'evname' => ''
);
$eventDetailsSql = "SELECT COUNT(*) As evcount, max(EvName) as evname, max(EvId) as evid, max(EventDetails) as evdetails FROM VEventDetails Where EvStatus = ?";

// Prepare and execute the SQL statement to retrieve event details
$checkParamsEmployees = array('Current');
$eventDetailsStmt = sqlsrv_query($conn, $eventDetailsSql, $checkParamsEmployees); // Pass the parameters here

if ($eventDetailsStmt === false) {
  // Handle the query execution error
  die(print_r(sqlsrv_errors(), true)); // This will help you see the specific error details
}

// Fetch the result of the query
$eventDetails = sqlsrv_fetch_array($eventDetailsStmt);

if ($eventDetails['evcount'] > 0) {
  // Employee ID found in VEventDetails, you can retrieve the data
  $EvID = $eventDetails['evid'];
  $evname = $eventDetails['evname'];
  $EvDetails = $eventDetails['evdetails'];
} else {
  // No active event at the moment
  $_SESSION['message'] = ['type' => 'error', 'text' => 'There are currently no active event at the moment!'];
  $empID = '';
}
//******************************************************************************************************/
if (isset($_POST['btnsubmit'])) {


  if ($eventDetails['evcount'] > 0) {
  } else {
    // No active event at the moment
    $_SESSION['message'] = ['type' => 'error', 'text' => 'There are currently no active event at the moment!'];
    header("Location: index.php");
    $empID = '';
  }

  $empID = $_POST["EmpID"];
  $checkSqlEmployees = "SELECT COUNT(*) AS count, MAX(EmpName) AS EmpName FROM tbl_Employees WHERE EmpID = ?";

  $checkParamsEmployees = array($empID);
  $checkStmtEmployees = sqlsrv_query($conn, $checkSqlEmployees, $checkParamsEmployees);

  if ($checkStmtEmployees === false) {
    header("Location: error.html");
    die(print_r(sqlsrv_errors(), true));
  }
  // Fetch the result of the query for tbl_Employees
  $resultEmployees = sqlsrv_fetch_array($checkStmtEmployees);

  // Check if the employee ID is not found in tbl_Employees
  if ($resultEmployees['count'] == 0) {
    //header("Location: id_notfound.html");
    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Employee ID was not found!'];
    $empID = '';
  } else {
    // Employee ID found in tbl_Employees
    $employeeName = $resultEmployees['EmpName'];

    $checkSqlRegistration = "SELECT COUNT(*) AS count FROM VRegDetails WHERE EmpID = ? and EventAndDate = ?";

    $checkParamsRegistration = array($empID, $EvDetails);
    $checkStmtRegistration = sqlsrv_query($conn, $checkSqlRegistration, $checkParamsRegistration);

    if ($checkStmtRegistration === false) {
      die(print_r(sqlsrv_errors(), true));
      $_SESSION['message'] = ['type' => 'error', 'text' => 'An error occured! Please try again later.'];
    }

    $resultRegistration = sqlsrv_fetch_array($checkStmtRegistration);
    if ($resultRegistration['count'] == 0) {
      $insertSql = "INSERT INTO tbl_registration (EmpID, evID) VALUES (?,?)";

      $insertParams = array($empID, $EvID);
      $insertStmt = sqlsrv_query($conn, $insertSql, $insertParams);

      if ($insertStmt === false) {
        die(print_r(sqlsrv_errors(), true));
        header("Location: error.html");
      } else {
        //die(print_r(sqlsrv_errors(), true));
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Yeeey! You are now registered ' . $employeeName . '!'];
        $empID = '';
        //header("Location: index.php");
        sqlsrv_close($conn);
      }
    } else {

      $_SESSION['message'] = ['type' => 'error', 'text' => 'Employee ID is already registered in this event!'];
      //header("Location: index.php");
      $empID = '';
    }
  }
}; ?>


<!DOCTYPE html>
<html>

<head>
  <title>Registration Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css" />
  <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.4.0"></script>


  <link rel="stylesheet" type="text/css" href="styles/styles.css">
</head>

<body>
  <h2>Registration</h2>
  <form id="regForm" action="index.php" method="post">
    <label for="EmpID">Employee ID:</label>
    <input type="text" id="EmpID" name="EmpID" required><br><br>
    <p>Event Name: <?php echo $evname; ?></p>
    <button type="submit" name="btnsubmit" class="btn btn-primary mb-3" onclick="checkEmpID()">Register</button>
  </form>

  <!-- JavaScript -->
  <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>

  <script>
    <?php
    if (isset($_SESSION['message'])) {
      $message = $_SESSION['message'];
      $type = $message['type'];
      $text = $message['text'];
    ?>

      alertify.set('notifier', 'position', 'top-center');

      <?php
      if ($type === 'error') { ?>
        alertify.error('<?= $text; ?>');
      <?php
      } elseif ($type === 'warning') { ?>
        alertify.warning('<?= $text; ?>');
      <?php
      } elseif ($type === 'success') { ?>
        alertify.success('<?= $text; ?>');
    <?php
      }
      unset($_SESSION['message']);
    } ?>

function checkEmpID() {
      var empID = document.getElementById('EmpID').value;
      if (empID !== '') {
        createConfetti();
      }
    }

<?php if (isset($type) && $type === 'success') { ?>
    function createConfetti() {
    // Fireworks
    confetti({
      particleCount: 100,
      spread: 70,
      origin: { y: 0.6 },
      colors: ['#ff0000', '#ff7f00', '#ffff00', '#00ff00', '#0000ff', '#4b0082', '#8f00ff'], // rainbow colors
    });

    // School pride (let's assume the school colors are blue and white)
    confetti({
      particleCount: 100,
      spread: 70,
      origin: { y: 0.6 },
      colors: ['#0000ff', '#ffffff'],
    });

    
    // Realistic confetti
    confetti({
      particleCount: 100,
      spread: 70,
      origin: { y: 0.6 },
      colors: ['#ffffff', '#ffe5b4', '#ff7f50', '#d2691e', '#8b4513'], // shades of brown and white
    });

    // Stars
    confetti({
      particleCount: 100,
      spread: 70,
      origin: { y: 0.6 },
      shapes: ['square'], // stars can be approximated by squares
      colors: ['#ffff00'], // yellow stars
    });

    // Snow
    confetti({
      particleCount: 100,
      spread: 70,
      origin: { y: 0.6 },
      colors: ['#ffffff'], // white snow 
    });
  }
  window.onload = createConfetti;
    <?php } ?>
  </script>
</body>
</html>