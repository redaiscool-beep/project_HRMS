-- ผู้ใช้ที่ mock_data.sql อ้างถึง (hr_employee_movement.created_by, hr_leave_request.approved_by)
-- ต้องรันหลัง schema.sql และก่อน mock_data.sql
-- password_hash เป็นค่าที่ login ไม่ได้ ให้ตั้งรหัสผ่านจริงด้วย:
--   php scripts/create_user.php admin <password>

INSERT INTO public.train_users (id, username, password_hash, role, is_active)
VALUES (1, 'admin', '!', 'ADMIN', true);
