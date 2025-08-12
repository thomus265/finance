<?php
class Visits{
    private $db;
    public function __construct(){
        $this->db = new Database();
    }

    public function todayVisits(){
        $return =  $this->db->query("SELECT COUNT(*) AS visit_count FROM visits WHERE DATE(session_start) = CURDATE()")->fetchArray();
        return $return['visit_count'];
    }
    public function weekVisits(){
        $return =  $this->db->query("SELECT 
        YEAR(session_start) AS visit_year,
        WEEK(session_start) AS visit_week,
        COUNT(*) AS visit_count
    FROM 
        visits
    GROUP BY 
        visit_year, visit_week
    ORDER BY 
        visit_year, visit_week;
     ")->fetchArray();

     if(!$return){
        return 0;
     }
     else return $return['visit_count'];
    }

    function blog_views(){
        $return = $this->db->query("SELECT SUM(count) AS blog_count FROM counter")->fetchArray();
        return $return['blog_count'];
    }

    function trending_blog(){
        return $this->db->query("SELECT * FROM counter ORDER BY count DESC LIMIT 1")->fetchArray();
        
    }

    function getLast5Days(){
        $current_date = date('Y-m-d');

        // Prepare an array to hold the visit counts
        $visits = array();

        // Loop through the past 5 days
        for ($i = 0; $i < 7; $i++) {
            // Calculate the date for each of the past 5 days
            $date = date('Y-m-d', strtotime("$current_date -$i days"));
            if ($i == 0) {
                $dayLabel = "Today";
            } else {
                $dayLabel = date('l', strtotime($date)); // Get the day of the week
            }
            // SQL query to get the visit count for each date
            $result = $this->db->query("SELECT COUNT(*) AS total_visits FROM visits WHERE DATE(session_start)  = '$date'")->fetchArray();
            // Check if there are results
            if ($result) {
                // Fetch the data
                
                $visits[$dayLabel] = $result['total_visits'];
            } else {
                // If no visits, set count to 0
                $visits[$date] = 0;
            }
        }
        return $visits;
    }


}
?>