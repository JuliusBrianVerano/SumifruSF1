<?php
// Database configuration
session_start();
include 'db/db_connection.php';
// Create a connection to the database
$conn = sqlsrv_connect($serverName, $connectionOptions);


if (!$conn) {
    //header("Location: error.html");
    $_SESSION['message'] = ['type' => 'error', 'text' => 'A connection error occured. Please try again later!'];
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
}
//******************************************************************************************************/
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the employee ID from the form
    if ($eventDetails['evcount'] > 0) {
        // Employee ID found in VEventDetails, you can retrieve the data
        $EvID = $eventDetails['evid'];
        $evname = $eventDetails['evname'];
        $EvDetails = $eventDetails['evdetails'];
    } else {
        // No active event at the moment
        $_SESSION['message'] = ['type' => 'error', 'text' => 'There are currently no active event at the moment!'];
        return;
    }
    $empID = $_POST["EmpID"];
    $checkSqlEmployees = "SELECT COUNT(*) AS count, MAX(EmpName) AS EmpName FROM tbl_Employees WHERE EmpID = ?";

    $checkParamsEmployees = array($empID);
    $checkStmtEmployees = sqlsrv_query($conn, $checkSqlEmployees, $checkParamsEmployees);

    if ($checkStmtEmployees === false) {
        //header("Location: error.html");
        die(print_r(sqlsrv_errors(), true));
    }
    // Fetch the result of the query for tbl_Employees
    $resultEmployees = sqlsrv_fetch_array($checkStmtEmployees);

    // Check if the employee ID is not found in tbl_Employees
    if ($resultEmployees['count'] == 0) {
        //header("Location: id_notfound.html");
        $_SESSION['message'] = ['type' => 'warning', 'text' => 'Employee ID was not found!'];
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
                //die(print_r(sqlsrv_errors(), true));
                //header("Location: error.html");
            } else {
                //die(print_r(sqlsrv_errors(), true));
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Yeeey! You are now registered ' . $employeeName . '!'];
                //header("Location: thank_you.html?name=" . urlencode($employeeName));
                sqlsrv_close($conn);
            }
        } else {
            //Display Already Registsred
            //die(print_r(sqlsrv_errors(), true));
            //header("Location: id_alreadyregistered.html");
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Employee ID already registered in this event!'];
        }
    }
};
?>