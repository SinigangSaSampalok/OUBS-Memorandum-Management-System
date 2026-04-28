<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks
        $this->db->disableForeignKeyChecks();
        
        // Clear existing data
        $this->db->table('bor_members')->truncate();
        $this->db->table('users')->truncate();
        $this->db->table('college_campuses')->truncate();
        
        // Re-enable foreign key checks
        $this->db->enableForeignKeyChecks();

        $collegeCampuses = [
            ['name' => 'College of Agriculture', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Arts and Humanities', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Natural Science', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Engineering', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Forestry', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Human Kinetics', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Home Economics and Technology', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Numeracy and Applied Sciences', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Social Science', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Nursing', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Veterinary Medicine', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Medicine', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Public Administration and Governance', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Information Science', 'type' => 'college', 'is_active' => true],
            ['name' => 'College of Teacher Education', 'type' => 'college', 'is_active' => true],
            ['name' => 'BOKOD CAMPUS', 'type' => 'campus', 'is_active' => true],
            ['name' => 'BUGIAS CAMPUS', 'type' => 'campus', 'is_active' => true],
        ];

        $this->db->table('college_campuses')->insertBatch($collegeCampuses);

        // OUBS Account (password: oubs123)
        $oubsPassword = password_hash('oubs123', PASSWORD_BCRYPT);
        
        $users = [
            // OUBS Account
            [
                'username' => 'oubs',
                'full_name' => 'Office of University Board Secretary',
                'email' => 'oubs@university.edu',
                'password' => $oubsPassword,
                'user_type' => 'oubs',
                'position' => 'Secretary',
                'is_active' => true,
            ],

            // BOR Members (12 members)
            [
                'username' => 'bor01',
                'full_name' => 'Dr. Juan Dela Cruz',
                'email' => 'juan.delacruz@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Chairperson',
                'is_active' => true,
            ],
            [
                'username' => 'bor02',
                'full_name' => 'Dr. Maria Santos',
                'email' => 'maria.santos@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Vice Chairperson',
                'is_active' => true,
            ],
            [
                'username' => 'bor03',
                'full_name' => 'Atty. Pedro Reyes',
                'email' => 'pedro.reyes@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor04',
                'full_name' => 'Dr. Ana Lopez',
                'email' => 'ana.lopez@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor05',
                'full_name' => 'Engr. Roberto Lim',
                'email' => 'roberto.lim@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor06',
                'full_name' => 'Dr. Sofia Garcia',
                'email' => 'sofia.garcia@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor07',
                'full_name' => 'Mr. Carlos Tan',
                'email' => 'carlos.tan@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor08',
                'full_name' => 'Dr. Elena Rodriguez',
                'email' => 'elena.rodriguez@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor09',
                'full_name' => 'Atty. Miguel Cruz',
                'email' => 'miguel.cruz@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor10',
                'full_name' => 'Dr. Lourdes Mendoza',
                'email' => 'lourdes.mendoza@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor11',
                'full_name' => 'Mr. Antonio Torres',
                'email' => 'antonio.torres@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],
            [
                'username' => 'bor12',
                'full_name' => 'Dr. Carmen Santos',
                'email' => 'carmen.santos@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Member',
                'is_active' => true,
            ],

            // UAC Members
            [
                'username' => 'uac01',
                'full_name' => 'Dr. Ricardo Alonzo',
                'email' => 'ricardo.alonzo@university.edu',
                'password' => '',
                'user_type' => 'uac',
                'position' => 'President',
                'college_campus_id' => 1,
                'is_active' => true,
            ],
            [
                'username' => 'uac02',
                'full_name' => 'Dr. Beatriz Ramos',
                'email' => 'beatriz.ramos@university.edu',
                'password' => '',
                'user_type' => 'uac',
                'position' => 'Vice President',
                'college_campus_id' => 2,
                'is_active' => true,
            ],
            [
                'username' => 'uac03',
                'full_name' => 'Dr. Fernando Cruz',
                'email' => 'fernando.cruz@university.edu',
                'password' => '',
                'user_type' => 'uac',
                'position' => 'Dean',
                'college_campus_id' => 3,
                'is_active' => true,
            ],

            // UAdmin Members
            [
                'username' => 'uadmin01',
                'full_name' => 'Ms. Isabel Cortez',
                'email' => 'isabel.cortez@university.edu',
                'password' => '',
                'user_type' => 'uadmin',
                'position' => 'Administrative Officer',
                'is_active' => true,
            ],
            [
                'username' => 'uadmin02',
                'full_name' => 'Mr. Alfredo Santos',
                'email' => 'alfredo.santos@university.edu',
                'password' => '',
                'user_type' => 'uadmin',
                'position' => 'Finance Officer',
                'is_active' => true,
            ],
            [
                'username' => 'uadmin03',
                'full_name' => 'Ms. Patricia Reyes',
                'email' => 'patricia.reyes@university.edu',
                'password' => '',
                'user_type' => 'uadmin',
                'position' => 'HR Officer',
                'is_active' => true,
            ],
        ];

        // Insert users one by one to avoid batch issues
        foreach ($users as $user) {
            $this->db->table('users')->insert($user);
        }

        // Get BOR user IDs (users with IDs 2-13)
        $borMembers = [
            ['user_id' => 2, 'member_number' => 1, 'committee_role' => 'Chairperson'],
            ['user_id' => 3, 'member_number' => 2, 'committee_role' => 'Vice Chairperson'],
            ['user_id' => 4, 'member_number' => 3, 'committee_role' => 'Academic Affairs Committee'],
            ['user_id' => 5, 'member_number' => 4, 'committee_role' => 'Finance Committee'],
            ['user_id' => 6, 'member_number' => 5, 'committee_role' => 'Infrastructure Committee'],
            ['user_id' => 7, 'member_number' => 6, 'committee_role' => 'Student Affairs Committee'],
            ['user_id' => 8, 'member_number' => 7, 'committee_role' => 'Research Committee'],
            ['user_id' => 9, 'member_number' => 8, 'committee_role' => 'Legal Affairs Committee'],
            ['user_id' => 10, 'member_number' => 9, 'committee_role' => 'International Relations'],
            ['user_id' => 11, 'member_number' => 10, 'committee_role' => 'Quality Assurance'],
            ['user_id' => 12, 'member_number' => 11, 'committee_role' => 'Alumni Relations'],
            ['user_id' => 13, 'member_number' => 12, 'committee_role' => 'Community Extension'],
        ];

        $this->db->table('bor_members')->insertBatch($borMembers);

        echo "Users seeded successfully!\n";
        echo "OUBS login: password = oubs123\n";
        echo "Recipient users: password is not set yet (use Set Password on login)\n";
    }
}
