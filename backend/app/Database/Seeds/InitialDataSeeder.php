<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
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

        // Users table
        $users = [
            // OUBS Account
            [
                'username' => 'oubs',
                'full_name' => 'Office of University Board Secretary',
                'email' => 'oubs@university.edu',
                'password' => password_hash('oubs123', PASSWORD_DEFAULT),
                'user_type' => 'oubs',
                'position' => 'Secretary',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
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
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'username' => 'bor02',
                'full_name' => 'Dr. Maria Santos',
                'email' => 'maria.santos@university.edu',
                'password' => '',
                'user_type' => 'bor',
                'position' => 'Vice Chairperson',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Add more BOR members here (bor03 to bor12)
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
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Add more UAC members
            // UAdmin Members
            [
                'username' => 'uadmin01',
                'full_name' => 'Ms. Isabel Cortez',
                'email' => 'isabel.cortez@university.edu',
                'password' => '',
                'user_type' => 'uadmin',
                'position' => 'Administrative Officer',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Add more UAdmin members
        ];

        $this->db->table('users')->insertBatch($users);

        // BOR Members table
        $borMembers = [
            ['user_id' => 2, 'member_number' => 1, 'committee_role' => 'Chairperson'],
            ['user_id' => 3, 'member_number' => 2, 'committee_role' => 'Vice Chairperson'],
            // Add more BOR members
        ];

        $this->db->table('bor_members')->insertBatch($borMembers);

        // Document Types
        $documentTypes = [
            [
                'type_name' => 'Board Resolution',
                'recipient_type' => 'bor',
                'description' => 'Resolutions for Board of Regents approval'
            ],
            [
                'type_name' => 'Annual Budget',
                'recipient_type' => 'bor',
                'description' => 'University annual budget proposal'
            ],
            [
                'type_name' => 'Academic Policies',
                'recipient_type' => 'bor',
                'description' => 'Academic policy changes'
            ],
            [
                'type_name' => 'Administrative Memo',
                'recipient_type' => 'uadmin',
                'description' => 'Administrative memorandums'
            ],
            [
                'type_name' => 'Academic Calendar',
                'recipient_type' => 'uac',
                'description' => 'University academic calendar'
            ],
            [
                'type_name' => 'Faculty Appointments',
                'recipient_type' => 'bor',
                'description' => 'Faculty hiring and promotions'
            ],
        ];

        $this->db->table('document_types')->insertBatch($documentTypes);

        // Sample Documents
        $documents = [
            [
                'document_number' => 'MEMO-2024-001',
                'title' => 'Annual Budget Proposal 2024',
                'description' => 'University budget for fiscal year 2024',
                'file_path' => 'uploads/documents/budget2024.pdf',
                'file_name' => 'budget2024.pdf',
                'file_size' => 2048000,
                'file_type' => 'application/pdf',
                'recipient_type' => 'bor',
                'uploaded_by' => 1,
                'status' => 'pending',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'document_number' => 'MEMO-2024-002',
                'title' => 'New Academic Calendar',
                'description' => 'Academic calendar for school year 2024-2025',
                'file_path' => 'uploads/documents/calendar2024.pdf',
                'file_name' => 'calendar2024.pdf',
                'file_size' => 1024000,
                'file_type' => 'application/pdf',
                'recipient_type' => 'uac',
                'uploaded_by' => 1,
                'status' => 'pending',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        $this->db->table('documents')->insertBatch($documents);
    }
}
