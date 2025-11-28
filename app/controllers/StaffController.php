<?php
// app/controllers/StaffController.php

class StaffController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getDashboardData() {
        // Initialize default return data (handles potential errors gracefully)
        $data = [
            'pendingRequests' => 0,
            'outstandingPenalties' => 0,
            'recentActivity' => []
        ];

        try {
            // 1. Get total pending borrowing requests (Status='Reserved')
            // UPDATED: Using 'borrowing_record' table
            $stmt1 = $this->pdo->query("SELECT COUNT(BorrowID) AS pending FROM borrowing_record WHERE Status = 'Reserved'");
            $stats1 = $stmt1->fetch();
            $data['pendingRequests'] = $stats1['pending'] ?? 0;

            // 2. Get total outstanding (pending) penalties
            $stmt2 = $this->pdo->query("SELECT COUNT(PenaltyID) AS outstanding FROM penalty WHERE Status = 'Pending'");
            $stats2 = $stmt2->fetch();
            $data['outstandingPenalties'] = $stats2['outstanding'] ?? 0;

            // 3. Get recent operational activity (Borrow/Return)
            // UPDATED: Now queries 'borrowing_record' directly instead of the old audit table
            $sql_activity = "
                SELECT 
                    BR.BorrowDate AS ActionTimestamp, 
                    BR.Status AS ActionType, 
                    BK.Title, 
                    U.Name AS UserName
                FROM borrowing_record BR
                JOIN users U ON BR.UserID = U.UserID
                JOIN book_copy BC ON BR.CopyID = BC.CopyID
                JOIN book BK ON BC.BookID = BK.BookID
                WHERE BR.Status IN ('Borrowed', 'Returned')
                ORDER BY BR.BorrowDate DESC
                LIMIT 5
            ";
            $stmt3 = $this->pdo->query($sql_activity);
            $data['recentActivity'] = $stmt3->fetchAll();

        } catch (PDOException $e) {
            error_log("StaffController Error: " . $e->getMessage());
            // Return default data on error
        }

        return $data;
    }
}
?>