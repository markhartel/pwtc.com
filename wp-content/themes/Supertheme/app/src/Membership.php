<?php
namespace App;

// I have this as its own class so it is better organized than throwing in functions.php
// Because of the nature of wordpress this class is tightly coupled and does not use dependency injection like I normally would
use Swift_Mailer;
use Swift_MailTransport;

class Membership
{
    /** @var Swift_Mailer Notification */
    protected $notifier;

    public function __construct()
    {
        $transport = Swift_MailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);
        $this->notifier = new Notification(
            "wordpress@pwtc.com",
            "pwtc.com",
            get_field('membership_captain_email', 'option'),
            get_field('membership_captain_name', 'option'),
            $mailer
        );
        
        add_action('wp_ajax_basic_info', [$this, "updateBasicInfo"]);
        add_action('wp_ajax_nopriv_basic_info', [$this, "updateBasicInfo"]);

        add_action('wp_ajax_delete_household', [$this, "deleteMember"]);
        add_action('wp_ajax_nopriv_delete_household', [$this, "deleteMember"]);

        add_action('wp_ajax_add_household', [$this, "addMember"]);
        add_action('wp_ajax_nopriv_add_household', [$this, "addMember"]);
    }

    public function updateBasicInfo()
    {
        // get user information
        $wordpress_user = get_userdata(get_current_user_id());
        if(!$wordpress_user) {
            echo "Must be logged in";
            die();
        }

        // vaidate wordpress info
        $first = $_POST['first'];
        $last = $_POST['last'];
        $email = $_POST['email'];
        $email_id = $_POST['email_id'];
        $email_location = $_POST['email_location'];

        // update wordpress user
        $wordpress_update = [
            'ID' => $wordpress_user->ID,
            'first_name' => $first,
            'last_name' => $last,
        ];
        if($wordpress_user->user_email != $email) {
            $wordpress_update['user_email'] = $email;
        }
        $result = wp_update_user($wordpress_update);
        if(!$result) {
            echo "Unable to update user information";
            die();
        }

        // validate civi id
        $contact_id = $_POST['contact_id'];

        // update civi contact
        $result = civicrm_api3('Contact', 'create', array(
            'sequential' => 1,
            'id' => $contact_id,
            'first_name' => $first,
            'last_name' => $last,
        ));
        $result = civicrm_api3('Email', 'create', array(
            'sequential' => 1,
            'contact_id' => $contact_id,
            'id' => $email_id,
            'email' => $email,
            'location_type_id' => $email_location,
        ));

        // update phones
        for($i = 0; $i < count($_POST['phone']); $i++) {
            $phone_id = $_POST['phone_id'][$i];
            $phone_number = $_POST['phone'][$i];
            $phone_location = $_POST['phone_location'][$i];
            $phone_type = $_POST['phone_type'][$i];
            $primary = $_POST['phone_primary'] == $phone_id ? 1 : 0;

            $result = civicrm_api3('Phone', 'create', array(
                'sequential' => 1,
                'contact_id' => $contact_id,
                'id' => $phone_id,
                'phone' => $phone_number,
                'location_type_id' => $phone_location,
                'is_primary' => $primary,
                'phone_type_id' => $phone_type,
            ));
        }

        // update addressses
        for($i = 0; $i < count($_POST['address_type']); $i++) {
            $address_id = $_POST['address_id'][$i];
            $address_type = $_POST['address_type'][$i];
            $address_1 = $_POST['address_1'][$i];
            $address_2 = $_POST['address_2'][$i];
            $city = $_POST['address_city'][$i];
            $state = $_POST['address_state'][$i];
            $zip = $_POST['address_zip'][$i];
            $primary = $_POST['phone'][$i] ? 1 : 0;

            $result = civicrm_api3('Address', 'create', array(
                'sequential' => 1,
                'contact_id' => $contact_id,
                'id' => $address_id,
                'location_type_id' => $address_type,
                'street_address' => $address_1,
                'supplemental_address_1' => $address_2,
                'city' => $city,
                'state_province_id' => $state,
                'postal_code' => $zip,
                'is_primary' => $primary,
            ));
        }

        // notification
        $this->notifier->send("$first $last updated their account on pwtc.com", <<<HTML
            <p>$first $last has updated their information in WordPress.</p>
            <ul>
                <li>WordPress ID: {$wordpress_user->ID}</li>
                <li>CiviCRM ID: $contact_id</li>
            </ul>
HTML
        );

        // message to user
        echo "Account has been updated";
        die();
    }
    
    public function deleteMember()
    {
        // validation
        $contact_id = $_POST['id'];

        if(!isset($_POST['name'])) {
            $contact_name = $_POST['name'];
        } else {
            $contact_name = $_POST['name'];
        }
        $contact_name = htmlentities($contact_name);

        // process
        civicrm_initialize();
        $result = civicrm_api3('Relationship', 'delete', array(
            'sequential' => 1,
            'id' => $contact_id,
        ));

        // notification
        $this->notifier->send("someone has been removed from a family membership", <<<HTML
            <p>$contact_name has been removed from a family membership</p>
HTML
        );
        
        // message to user
        echo "$contact_name has been removed from the household";
        die();
    }
    
    public function addMember()
    {
        // get current user contact
        $wordpress_user = get_userdata(get_current_user_id());
        civicrm_initialize();
        $result = civicrm_api3('contact', 'get', array(
            'sequential' => 1,
            'email' => $wordpress_user->user_email,
        ));
        $contact_id = $result['values'][0]['contact_id'];

        // attempt to get the household contact
        $result = civicrm_api3('Relationship', 'get', array(
            'sequential' => 1,
            'relationship_type_id' => 6,
            'contact_id_a' => $contact_id,
        ));

        if($result['values']) {
            $household_id = $result['values'][0]['contact_id_b'];
        }

        // create a household with the current contact as the head if one doesn't already exist
        if(!isset($household_id) || !$household_id) {
            $result = civicrm_api3('Contact', 'create', array(
                'sequential' => 1,
                'contact_type' => "Household",
                'household_name' => $wordpress_user->user_firstname . " " . $wordpress_user->user_lastname,
                //'primary_contact_id' => $contact_id,
            ));
            $household_id = $result['values'][0]['id'];
            $result = civicrm_api3('Relationship', 'create', array(
                'sequential' => 1,
                'contact_id_a' => $contact_id,
                'contact_id_b' => $household_id,
                'relationship_type_id' => 6,
            ));
        }

        // check if user already has a contact by looking for the email
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $result = civicrm_api3('contact', 'get', array(
            'sequential' => 1,
            'email' => $email,
        ));

        // create new contact if one doesnt exist
        if(!$result['values']) {
            $result = civicrm_api3('Contact', 'create', array(
                'sequential' => 1,
                'contact_type' => "Individual",
                'first_name' => $first_name,
                'last_name' => $last_name,
            ));
            $household_member_id = $result['values'][0]['id'];
            // add email to contact
            $result = civicrm_api3('Email', 'create', array(
                'sequential' => 1,
                'contact_id' => $household_member_id,
                'email' => $email,
            ));

            //@todo add primary address?
        } else {
            $household_member_id = $result['values'][0]['contact_id'];
        }

        // new user household
        $result = civicrm_api3('Relationship', 'create', array(
            'sequential' => 1,
            'contact_id_a' => $household_member_id,
            'contact_id_b' => $household_id,
            'relationship_type_id' => 7,
        ));

        // create user if one doesnt exist
        $username = $first_name . '-' . $last_name;
        $username = preg_replace("/[^A-Za-z0-9]+/", '-', $username);
        $username_id = username_exists($username);
        $email_id = email_exists($email);
        if (!$username_id && !$email_id) {
            $user_id = register_new_user($username, $email);
            $user_id = wp_update_user(['ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name]);
        } else {
            $user_id = $email_id;
        }
        
        if(!$user_id) {
            echo "Error. Failed to create the WordPress account";
            die();
        }

        // notification
        $this->notifier->send("$first_name $last_name has been added to a family membership", <<<HTML
            <p>$first_name $last_name has been added to {$wordpress_user->user_firstname} {$wordpress_user->user_lastname} family membership.</p>
            <ul>
                <li>WordPress ID: $user_id</li>
                <li>CiviCRM ID: $household_member_id</li>
            </ul>
HTML
        );

        // message to user
        echo "$first_name $last_name has been added to the household. An email was sent to them with login instructions.";
        die();
    }
}