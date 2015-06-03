<?php
/*
  $Id$
	
	extension to splitPageResults to handle union queries
	
	Author: john@sewebsites.net BrockleyJohn

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  class splitUnionResults extends splitPageResults {
		var $debug;

/* class constructor */
    function splitUnionResults($query, $max_rows, $count_key = '*', $page_holder = 'page') {

      $this->sql_query = $query;
      $this->page_name = $page_holder;

      if (isset($_GET[$page_holder])) {
        $page = $_GET[$page_holder];
      } elseif (isset($_POST[$page_holder])) {
        $page = $_POST[$page_holder];
      } else {
        $page = '';
      }

      if (empty($page) || !is_numeric($page)) $page = 1;
      $this->current_page_number = $page;

      $this->number_of_rows_per_page = $max_rows;
			$union = false;
			
			if ($pos_union = strpos($this->sql_query, 'union')) { // so it's actually two queries (could be more but we're assuming two)... start with the first

        $union = true;
				$pos_to = $pos_union;
				$pos_from = strpos($this->sql_query, ' from', 0);
	
				$pos_group_by = strpos($this->sql_query, ' group by', $pos_from);
				if (($pos_group_by < $pos_to) && ($pos_group_by != false) && ($pos_group_by < $pos_union)) $pos_to = $pos_group_by;
	
				$pos_having = strpos($this->sql_query, ' having', $pos_from);
				if (($pos_having < $pos_to) && ($pos_having != false) && ($pos_having < $pos_union)) $pos_to = $pos_having;
	
				$pos_order_by = strpos($this->sql_query, ' order by', $pos_from);
				if (($pos_order_by < $pos_to) && ($pos_order_by != false) && ($pos_order_by < $pos_union)) $pos_to = $pos_order_by;
	
				if ((strpos($this->sql_query, 'distinct') && strpos($this->sql_query, 'distinct') < $pos_union) || (strpos($this->sql_query, 'group by') && strpos($this->sql_query, 'group by') < $pos_union)) {
					$count_string = 'distinct ' . tep_db_input($count_key);
				} else {
					$count_string = tep_db_input($count_key);
				}
				$count_sql1 = "select count(" . $count_string . ") as total " . substr($this->sql_query, $pos_from, ($pos_to - $pos_from));
				
				// second part of query...
				$pos_to = strlen($this->sql_query);
				$pos_from = strpos($this->sql_query, ' from', $pos_union);
				
				$pos_group_by = strpos($this->sql_query, ' group by', $pos_from);
				if (($pos_group_by < $pos_to) && ($pos_group_by != false)) $pos_to = $pos_group_by;
	
				$pos_having = strpos($this->sql_query, ' having', $pos_from);
				if (($pos_having < $pos_to) && ($pos_having != false)) $pos_to = $pos_having;
	
				$pos_order_by = strpos($this->sql_query, ' order by', $pos_from);
				if (($pos_order_by < $pos_to) && ($pos_order_by != false)) $pos_to = $pos_order_by;
	
				if (strpos($this->sql_query, 'distinct', $pos_union) || strpos($this->sql_query, 'group by', $pos_union)) {
					$count_string = 'distinct ' . tep_db_input($count_key);
				} else {
					$count_string = tep_db_input($count_key);
				}
				$count_sql2 = "select count(" . $count_string . ") as total " . substr($this->sql_query, $pos_from, ($pos_to - $pos_from));
				
				$count_sql = '(' . $count_sql1 . ') union (' . $count_sql2 . ')';
			
				$this->debug = $count_sql;
			} else { // original build of count query
				$pos_to = strlen($this->sql_query);
				$pos_from = strpos($this->sql_query, ' from', 0);
	
				$pos_group_by = strpos($this->sql_query, ' group by', $pos_from);
				if (($pos_group_by < $pos_to) && ($pos_group_by != false)) $pos_to = $pos_group_by;
	
				$pos_having = strpos($this->sql_query, ' having', $pos_from);
				if (($pos_having < $pos_to) && ($pos_having != false)) $pos_to = $pos_having;
	
				$pos_order_by = strpos($this->sql_query, ' order by', $pos_from);
				if (($pos_order_by < $pos_to) && ($pos_order_by != false)) $pos_to = $pos_order_by;
	
				if (strpos($this->sql_query, 'distinct') || strpos($this->sql_query, 'group by')) {
					$count_string = 'distinct ' . tep_db_input($count_key);
				} else {
					$count_string = tep_db_input($count_key);
				}
				$count_sql = "select count(" . $count_string . ") as total " . substr($this->sql_query, $pos_from, ($pos_to - $pos_from));
			}

      $count_query = tep_db_query($count_sql);
      $count = tep_db_fetch_array($count_query);

      $total = $count['total'];
      if ($union) {
				$count = tep_db_fetch_array($count_query);
			  $total = $total + $count['total'];
			}

      $this->number_of_rows = $total;

      $this->number_of_pages = ceil($this->number_of_rows / $this->number_of_rows_per_page);

      if ($this->current_page_number > $this->number_of_pages) {
        $this->current_page_number = $this->number_of_pages;
      }

      $offset = ($this->number_of_rows_per_page * ($this->current_page_number - 1));

      $this->sql_query .= " limit " . max($offset, 0) . ", " . $this->number_of_rows_per_page;
    }

  }
?>