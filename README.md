# TaskMaster Pro - Quản Lý Công Việc Thông Minh

**TaskMaster Pro** là một ứng dụng quản lý công việc mạnh mẽ và trực quan, giúp bạn và đội nhóm dễ dàng lập kế hoạch, theo dõi tiến độ và hoàn thành dự án một cách hiệu quả.

🚀 **Demo trực tiếp tại:** [task.nioo.io.vn](https://www.google.com/search?q=http://task.nioo.io.vn)

✨ **Điểm đặc biệt: Toàn bộ dự án này được hỗ trợ phát triển bởi Gemini AI từ Google.**

## Giới thiệu

Bạn đang tìm kiếm một công cụ để sắp xếp cuộc sống bận rộn, quản lý các dự án cá nhân hoặc phối hợp công việc nhóm một cách trơn tru? TaskMaster Pro được thiết kế để đáp ứng chính xác những nhu cầu đó. Với giao diện thân thiện, dễ sử dụng cùng các tính năng mạnh mẽ, TaskMaster Pro sẽ là trợ thủ đắc lực giúp bạn chinh phục mọi mục tiêu.

## Ngôn ngữ & Công nghệ sử dụng

Dự án được xây dựng với các công nghệ hiện đại và phổ biến:

  * **Ngôn ngữ chính:** PHP (cho backend và xử lý logic)
  * **Giao diện người dùng (Frontend):**
      * HTML5
      * **Tailwind CSS**: Một framework CSS utility-first để xây dựng giao diện nhanh chóng và tùy biến cao.
      * **Alpine.js**: Một framework JavaScript nhỏ gọn để thêm các hành vi tương tác trực tiếp trên HTML.
  * **Cơ sở dữ liệu:** MySQL (được quản lý thông qua PDO trong PHP)
  * **Các thư viện/kỹ thuật khác:**
      * Session PHP để quản lý trạng thái đăng nhập.
      * AJAX cho các tương tác không đồng bộ (ví dụ: thêm task, cập nhật trạng thái).
      * Font Awesome cho các icon.

## Tính năng nổi bật

  * **Quản lý danh sách công việc linh hoạt**: Tạo, sửa, xóa các danh sách công việc theo dự án hoặc mục tiêu.
  * **Quản lý công việc chi tiết**: Thêm, sửa, xóa, đánh dấu hoàn thành các công việc trong từng danh sách.
  * **Chia sẻ danh sách**: Chia sẻ danh sách công việc với người khác thông qua liên kết tùy chỉnh, có thể đặt mật khẩu bảo vệ.
  * **Lịch sử chỉnh sửa & Khôi phục**: Theo dõi lịch sử thay đổi của từng công việc và khôi phục các phiên bản trước đó.
  * **Giao diện người dùng trực quan**: Dễ dàng sử dụng ngay cả với người không rành về công nghệ.
  * **Phân trang**: Hỗ trợ phân trang cho danh sách người dùng và danh sách link chia sẻ trong trang quản trị.
  * **Responsive Design**: Hoạt động tốt trên cả máy tính và thiết bị di động.
  * **Trang quản trị (Admin Panel)**: Quản lý người dùng và các liên kết chia sẻ.

## Hướng dẫn cài đặt và chạy dự án

### Yêu cầu

  * Một web server hỗ trợ PHP (ví dụ: Apache, Nginx)
  * PHP phiên bản 7.4 trở lên (khuyến nghị 8.x)
  * Cơ sở dữ liệu MySQL

### Bước 1: Clone Repository

Mở terminal hoặc command prompt của bạn và chạy lệnh sau:

```bash
git clone https://github.com/cnguyndev/taskmaster.git
```

### Bước 2: Cấu hình Cơ sở dữ liệu

1.  Tạo một cơ sở dữ liệu mới trong MySQL (ví dụ: `taskmaster_db`).
2.  Import file `database.sql` (nằm trong thư mục gốc của dự án) vào cơ sở dữ liệu vừa tạo. File này chứa cấu trúc bảng và một số dữ liệu mẫu (nếu có).
3.  Mở file `includes/db_connect.php`.
4.  Cập nhật các thông tin kết nối cơ sở dữ liệu cho phù hợp với môi trường của bạn:
    ```php
    $host = 'localhost'; // Hoặc IP/host của DB server
    $dbname = 'taskmaster_db'; // Tên cơ sở dữ liệu bạn đã tạo
    $username = 'root'; // Username của MySQL
    $password = ''; // Password của MySQL
    ```

### Bước 3: Chạy dự án

1.  Đặt toàn bộ thư mục dự án vào thư mục gốc của web server (ví dụ: `htdocs` cho XAMPP/Apache, `www` cho WAMP).
2.  Mở trình duyệt và truy cập vào đường dẫn của dự án trên server của bạn (ví dụ: `http://localhost/taskmaster/` hoặc `http://yourdomain.com/taskmaster/`).


## Đóng góp

Chúng tôi luôn chào đón sự đóng góp từ cộng đồng\! Nếu bạn muốn đóng góp, vui lòng fork repository, tạo một nhánh mới cho tính năng hoặc bản sửa lỗi của bạn, và sau đó tạo một Pull Request.
