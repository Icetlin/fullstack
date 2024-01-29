<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        var_dump(q);
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
            ];
        } else {
            return [
                'id' => 0,
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;

        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) {
            $where[] = "phone LIKE '%".$search."%'";
            $where[] = "first_name LIKE '%".$search."%'";
            $where[] = "email LIKE '%".$search."%'";
        }
        $where = $where ? "WHERE ".implode(" OR ", $where) : "";
        // info

        $q = DB::query("SELECT plot_id, first_name, last_name, phone, email, last_login
        FROM users ".$where." ORDER BY phone+0 LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['plot_id'],
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'phone' => (int) $row['phone'],
                'email' => (string) $row['email'],
                'last_login' => (int) $row['last_login'],
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search='.$search;
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = []) {
        $info = User::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user_id', $user_id);
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }


    public static function user_edit_update($d = []) {
        // vars
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;

        // vars
        $user_id = isset($d['user_id'][0]) && is_numeric($d['user_id'][0]) ? $d['user_id'][0] : 0;
        $update_data = [];

        // Check each field and add it to update_data if it is not empty
        foreach (['first_name', 'last_name', 'phone', 'email', 'plot_id'] as $field) {
            if (isset($d[$field][0]) && !empty($d[$field][0])) {
                if ($field === 'email') {
                    // Приводим email к нижнему регистру перед добавлением в update_data
                    $update_data[] = "$field='" . strtolower($d[$field][0]) . "'";
                } else {
                    $update_data[] = "$field='" . $d[$field][0] . "'";
                }
            }
        }

        $update_data[] = "updated='" . Session::$ts . "'";
        $update_query = implode(", ", $update_data);

        // update
        if ($user_id) {
            $query = "UPDATE users SET $update_query WHERE user_id='$user_id' LIMIT 1;";
            DB::query($query) or die(DB::error());
        } else {
            $query = "INSERT INTO users (
            first_name,
            last_name,
            phone,
            email,
            plot_id,
            updated
        ) VALUES (
            '" . implode("', '", array_values($d)) . "',
            '" . Session::$ts . "'
        );";
            DB::query($query) or die(DB::error());
        }
        // output
        return User::users_fetch(['offset' => $offset]);
    }

    public function delete($d = [])
    {
        $user_id = isset($d['user_id'][0]) && is_numeric($d['user_id'][0]) ? $d['user_id'][0] : 0;

        if ($user_id) {
            $query = "DELETE FROM users WHERE user_id='$user_id' LIMIT 1;";
            DB::query($query) or die(DB::error());
        }
    }
}
