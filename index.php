<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}

// Định nghĩa các biến cho layout_header.php
$pageTitle = "TaskMaster Pro - Chinh Phục Mục Tiêu, Bứt Phá Hiệu Suất";
$metaDescription = "TaskMaster Pro là giải pháp quản lý công việc thông minh...";
$metaKeywords = "quản lý công việc, to-do list...";
$ogImageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . "/taskmaster_project/images/og_image_taskmaster.png";
$isAppPageLayout = false; // Trang index không phải layout của app
$bodyClasses = 'h-full bg-slate-100 text-slate-800 antialiased flex flex-col'; // Thêm class x-data nếu cần

include 'includes/layout_header.php';
?>

<main class="flex-grow">
    <section class="bg-gradient-to-br from-indigo-700 via-purple-700 to-pink-600 text-white py-24 md:py-40 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
        </div>
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight fade-in-down">
                    <span class="block">Biến Hỗn Loạn Thành</span>
                    <span class="block text-indigo-300 via-pink-300 to-purple-300">Trật Tự Hoàn Hảo</span>
                </h1>
                <p class="text-lg md:text-xl lg:text-2xl mb-10 text-indigo-100 max-w-2xl mx-auto fade-in-up delay-1">
                    TaskMaster Pro giúp bạn làm chủ mọi công việc, dự án và mục tiêu. Đơn giản hóa quy trình, tối ưu hóa thời gian và bứt phá hiệu suất làm việc mỗi ngày.
                </p>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'app.php' : 'register.php'; ?>"
                    class="bg-white text-indigo-700 font-semibold px-10 py-4 rounded-lg shadow-2xl hover:bg-slate-100 transform hover:scale-105 transition duration-300 text-lg md:text-xl inline-block fade-in-up delay-2 group">
                    Bắt Đầu Miễn Phí <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </a>
                <p class="mt-6 text-sm text-indigo-200 fade-in-up delay-3">Không cần thẻ tín dụng. Đăng ký chỉ trong 1 phút.</p>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-24 bg-white" id="features">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12 md:mb-20">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-800 mb-4">Tính Năng Vượt Trội Của TaskMaster Pro</h2>
                <p class="text-slate-600 md:text-lg max-w-2xl mx-auto">Khám phá những công cụ mạnh mẽ giúp bạn quản lý công việc hiệu quả hơn bao giờ hết.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10">
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated bg-slate-50 p-8 rounded-xl shadow-lg hover:shadow-indigo-500/30 transition-all duration-300 transform hover:-translate-y-2">
                    <div class="flex justify-center items-center mb-6 w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full mx-auto">
                        <i class="fas fa-list-check fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-700 mb-3 text-center">Quản Lý Danh Sách Linh Hoạt</h3>
                    <p class="text-slate-600 text-sm leading-relaxed text-center">Tạo và tùy chỉnh không giới hạn số lượng danh sách công việc, dễ dàng sắp xếp theo dự án, mục tiêu hoặc ưu tiên cá nhân.</p>
                </div>
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated delay-1 bg-slate-50 p-8 rounded-xl shadow-lg hover:shadow-purple-500/30 transition-all duration-300 transform hover:-translate-y-2">
                    <div class="flex justify-center items-center mb-6 w-16 h-16 bg-purple-100 text-purple-600 rounded-full mx-auto">
                        <i class="fas fa-share-alt fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-700 mb-3 text-center">Chia Sẻ & Cộng Tác An Toàn</h3>
                    <p class="text-slate-600 text-sm leading-relaxed text-center">Chia sẻ danh sách với người khác qua liên kết tùy chỉnh, có thể đặt mật khẩu bảo vệ và kiểm soát quyền truy cập.</p>
                </div>
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated delay-2 bg-slate-50 p-8 rounded-xl shadow-lg hover:shadow-pink-500/30 transition-all duration-300 transform hover:-translate-y-2">
                    <div class="flex justify-center items-center mb-6 w-16 h-16 bg-pink-100 text-pink-600 rounded-full mx-auto">
                        <i class="fas fa-edit fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-700 mb-3 text-center">Chỉnh Sửa Công Việc Nhanh Chóng</h3>
                    <p class="text-slate-600 text-sm leading-relaxed text-center">Dễ dàng thêm mới, cập nhật nội dung, hoặc đánh dấu hoàn thành công việc chỉ với vài cú nhấp chuột, ngay cả trên di động.</p>
                </div>
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated delay-3 bg-slate-50 p-8 rounded-xl shadow-lg hover:shadow-teal-500/30 transition-all duration-300 transform hover:-translate-y-2">
                    <div class="flex justify-center items-center mb-6 w-16 h-16 bg-teal-100 text-teal-600 rounded-full mx-auto">
                        <i class="fas fa-history fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-700 mb-3 text-center">Lịch Sử & Khôi Phục Thông Minh</h3>
                    <p class="text-slate-600 text-sm leading-relaxed text-center">Theo dõi lịch sử thay đổi của từng công việc và khôi phục lại các phiên bản trước đó một cách dễ dàng, không lo mất dữ liệu.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-24 bg-slate-100" id="testimonials"
        x-data="testimonialSlider({
                autoplay: true, 
                autoplaySpeed: 5000,
                testimonials: [
                    { 
                        avatar: 'https://randomuser.me/api/portraits/men/32.jpg', 
                        name: 'Anh Minh Quân', 
                        title: 'Project Manager', 
                        quote: 'TaskMaster Pro thực sự là cứu cánh cho các dự án của chúng tôi. Việc theo dõi tiến độ và phân công công việc chưa bao giờ dễ dàng hơn thế.'
                    },
                    { 
                        avatar: 'https://randomuser.me/api/portraits/women/44.jpg', 
                        name: 'Chị Lan Anh', 
                        title: 'Content Creator', 
                        quote: 'Tôi yêu thích sự đơn giản nhưng mạnh mẽ của TaskMaster Pro. Nó giúp tôi quản lý lịch biên tập và các ý tưởng một cách khoa học.'
                    },
                    { 
                        avatar: 'https://randomuser.me/api/portraits/men/56.jpg', 
                        name: 'Bạn Quốc Bảo', 
                        title: 'Developer', 
                        quote: 'Khả năng chia sẻ danh sách với mật khẩu rất hữu ích khi làm việc nhóm. Lịch sử chỉnh sửa cũng là một điểm cộng lớn!'
                    },
                    { 
                        avatar: 'https://randomuser.me/api/portraits/women/68.jpg', 
                        name: 'Cô Thanh Mai', 
                        title: 'Giáo Viên', 
                        quote: 'Tôi dùng TaskMaster Pro để quản lý giáo án và các công việc cá nhân. Giao diện thân thiện và dễ sử dụng ngay cả với người không rành công nghệ.'
                    }
                ]
             })">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12 md:mb-20">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-800 mb-4">Hàng Ngàn Người Đã Tin Dùng</h2>
                <p class="text-slate-600 md:text-lg max-w-2xl mx-auto">Xem TaskMaster Pro đã thay đổi cách họ làm việc như thế nào.</p>
            </div>

            <div class="relative max-w-3xl mx-auto">
                <div class="overflow-hidden">
                    <div class="flex testimonial-slider-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
                        <template x-for="(testimonial, index) in testimonials" :key="index">
                            <div class="w-full flex-shrink-0 px-4">
                                <div class="bg-white p-8 rounded-lg shadow-xl min-h-[250px] flex flex-col justify-between">
                                    <p class="text-slate-600 text-base leading-relaxed italic mb-6" x-text="testimonial.quote"></p>
                                    <div class="flex items-center">
                                        <img :src="testimonial.avatar" alt="User Avatar" class="w-14 h-14 rounded-full mr-4 border-2 border-indigo-200">
                                        <div>
                                            <h4 class="font-semibold text-slate-800 text-lg" x-text="testimonial.name"></h4>
                                            <p class="text-sm text-indigo-500" x-text="testimonial.title"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <button @click="prevSlide"
                    class="absolute top-1/2 -left-4 md:-left-10 transform -translate-y-1/2 bg-white text-indigo-600 hover:bg-indigo-500 hover:text-white p-3 rounded-full shadow-lg transition-all duration-300 z-10"
                    aria-label="Đánh giá trước">
                    <i class="fas fa-chevron-left fa-lg"></i>
                </button>
                <button @click="nextSlide"
                    class="absolute top-1/2 -right-4 md:-right-10 transform -translate-y-1/2 bg-white text-indigo-600 hover:bg-indigo-500 hover:text-white p-3 rounded-full shadow-lg transition-all duration-300 z-10"
                    aria-label="Đánh giá kế tiếp">
                    <i class="fas fa-chevron-right fa-lg"></i>
                </button>

                <div class="absolute bottom-[-40px] left-1/2 transform -translate-x-1/2 flex space-x-2 mt-8">
                    <template x-for="(testimonial, index) in testimonials" :key="'dot-' + index">
                        <button @click="goToSlide(index)"
                            :class="{'bg-indigo-600 w-6': currentSlide === index, 'bg-slate-300 hover:bg-slate-400 w-3': currentSlide !== index}"
                            class="h-3 rounded-full transition-all duration-300"
                            :aria-label="'Xem đánh giá ' + (index + 1)"></button>
                    </template>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 lg:py-24 bg-white" id="how-it-works">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12 md:mb-20">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-800 mb-4">Làm Việc Thông Minh Hơn, Không Khó Hơn</h2>
                <p class="text-slate-600 md:text-lg max-w-3xl mx-auto">Với TaskMaster Pro, bạn chỉ cần 3 bước đơn giản để làm chủ mọi dòng công việc và đạt hiệu suất tối ưu.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-10">
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated text-center p-6 border border-slate-200 rounded-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="p-6 bg-indigo-100 text-indigo-600 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-8 shadow-lg">
                        <i class="fas fa-folder-plus fa-3x"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-slate-800 mb-3">1. Khởi Tạo & Sắp Xếp</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Tạo các <strong class="font-medium text-indigo-600">danh sách công việc</strong> riêng biệt cho từng dự án, phòng ban, hoặc mục tiêu cá nhân. Đặt tên rõ ràng, thêm mô tả chi tiết để mọi người cùng nắm bắt. Bạn có thể tạo bao nhiêu danh sách tùy ý!
                    </p>
                </div>
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated delay-1 text-center p-6 border border-slate-200 rounded-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="p-6 bg-purple-100 text-purple-600 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-8 shadow-lg">
                        <i class="fas fa-tasks-alt fa-3x"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-slate-800 mb-3">2. Thêm Việc & Quản Lý Chi Tiết</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Trong mỗi danh sách, hãy thêm các <strong class="font-medium text-purple-600">đầu việc cụ thể</strong>. Ghi chú nội dung công việc, chỉnh sửa nhanh chóng khi cần. Đánh dấu <strong class="font-medium text-purple-600">hoàn thành</strong> để theo dõi tiến độ. Chia sẻ danh sách với người khác nếu cần cộng tác.
                    </p>
                </div>
                <div x-intersect:enter="if ($el.classList.contains('animated')) { $el.classList.add('fade-in-up'); $el.classList.remove('animated'); }" class="animated delay-2 text-center p-6 border border-slate-200 rounded-xl hover:shadow-2xl transition-shadow duration-300">
                    <div class="p-6 bg-pink-100 text-pink-600 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-8 shadow-lg">
                        <i class="fas fa-clipboard-check fa-3x"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-slate-800 mb-3">3. Theo Dõi, Khôi Phục & Thành Công</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        Dễ dàng xem lại <strong class="font-medium text-pink-600">lịch sử chỉnh sửa</strong> của từng công việc. Nếu có sai sót, bạn có thể <strong class="font-medium text-pink-600">khôi phục phiên bản cũ</strong>. An tâm làm việc, chinh phục mục tiêu và tận hưởng sự hiệu quả mà TaskMaster Pro mang lại.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 lg:py-32 bg-gradient-to-br from-slate-800 to-slate-900">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6 fade-in-up">Sẵn Sàng Thay Đổi Cách Bạn Làm Việc?</h2>
            <p class="text-slate-300 md:text-xl mb-10 max-w-2xl mx-auto fade-in-up delay-1">Tham gia cùng hàng ngàn người dùng và bắt đầu quản lý công việc hiệu quả hơn với TaskMaster Pro ngay hôm nay.</p>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'app.php' : 'register.php'; ?>"
                class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-12 py-4 rounded-lg shadow-xl hover:shadow-indigo-500/50 transform hover:scale-105 transition duration-300 text-lg md:text-xl inline-block fade-in-up delay-2 group">
                Dùng Thử Miễn Phí Ngay <i class="fas fa-rocket ml-2 group-hover:rotate-12 transition-transform"></i>
            </a>
        </div>
    </section>
</main>

<?php
include 'includes/layout_footer.php';
?>