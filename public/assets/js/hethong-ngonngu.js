(function () {
    const STORAGE_KEY = "vtms.language";
    const DEFAULT_LANGUAGE = "vi";

    const accentMap = {
        "Nguoi dung": "Người dùng",
        "He thong": "Hệ thống",
        "He thong quan ly giai dau": "Hệ thống quản lý giải đấu",
        "Tong quan he thong": "Tổng quan hệ thống",
        "Man hinh dieu huong ban dau theo vai tro nguoi dung.": "Màn hình điều hướng ban đầu theo vai trò người dùng.",
        "Tong quan": "Tổng quan",
        "Quan tri he thong": "Quản trị hệ thống",
        "Quan ly": "Quản lý",
        "Quan ly tai khoan, vai tro, cau hinh va nhat ky.": "Quản lý tài khoản, vai trò, cấu hình và nhật ký.",
        "Nghiep vu": "Nghiệp vụ",
        "Ca nhan": "Cá nhân",
        "Trang chu": "Trang chủ",
        "Tai khoan": "Tài khoản",
        "Nguoi dung": "Người dùng",
        "Nhat ky he thong": "Nhật ký hệ thống",
        "Xac nhan thong tin BTC": "Xác nhận thông tin BTC",
        "Giai dau": "Giải đấu",
        "Lich thi dau": "Lịch thi đấu",
        "Doi bong": "Đội bóng",
        "San dau": "Sân đấu",
        "Trong tai": "Trọng tài",
        "Huan luyen vien": "Huấn luyện viên",
        "Van dong vien": "Vận động viên",
        "Quan ly giai dau, dieu le, doi bong, lich thi dau va ket qua.": "Quản lý giải đấu, điều lệ, đội bóng, lịch thi đấu và kết quả.",
        "Xem phan cong, ghi nhan su kien tran dau va bao cao su co.": "Xem phân công, ghi nhận sự kiện trận đấu và báo cáo sự cố.",
        "Quan ly doi bong, thanh vien, dang ky giai va doi hinh.": "Quản lý đội bóng, thành viên, đăng ký giải và đội hình.",
        "Theo doi ho so, lich thi dau, loi moi va don nghi phep.": "Theo dõi hồ sơ, lịch thi đấu, lời mời và đơn nghỉ phép.",
        "Khieu nai": "Khiếu nại",
        "Ket qua": "Kết quả",
        "Xep hang": "Xếp hạng",
        "Bang xep hang": "Bảng xếp hạng",
        "Xac nhan thong tin": "Xác nhận thông tin",
        "Lich phan cong": "Lịch phân công",
        "Giam sat tran dau": "Giám sát trận đấu",
        "Bao cao su co": "Báo cáo sự cố",
        "Xin nghi phep": "Xin nghỉ phép",
        "Doi bong cua toi": "Đội bóng của tôi",
        "Lich thi dau doi": "Lịch thi đấu đội",
        "Tai khoan VDV": "Tài khoản VĐV",
        "Thanh vien doi": "Thành viên đội",
        "Doi hinh": "Đội hình",
        "Dang ky giai dau": "Đăng ký giải đấu",
        "Yeu cau VDV": "Yêu cầu VĐV",
        "Thong bao": "Thông báo",
        "Loi moi doi bong": "Lời mời đội bóng",
        "Thong ke": "Thống kê",
        "Ho so": "Hồ sơ",
        "Nghi phep thi dau": "Nghỉ phép thi đấu",
        "Dang xuat": "Đăng xuất",
        "Cai dat": "Cài đặt",
        "Ngon ngu": "Ngôn ngữ",
        "Doi mat khau": "Đổi mật khẩu",
        "Bao mat tai khoan": "Bảo mật tài khoản",
        "Doi mat khau dang nhap": "Đổi mật khẩu đăng nhập",
        "Tim kiem": "Tìm kiếm",
        "Tim tran dau, doi bong...": "Tìm trận đấu, đội bóng...",

        "Trang chu quan tri he thong": "Trang chủ quản trị hệ thống",
        "Tong quan tai khoan, nguoi dung, yeu cau xac nhan va nhat ky van hanh.": "Tổng quan tài khoản, người dùng, yêu cầu xác nhận và nhật ký vận hành.",
        "Van hanh he thong tap trung, ro rang va co kiem soat": "Vận hành hệ thống tập trung, rõ ràng và có kiểm soát",
        "Theo doi tai khoan, phan quyen, ho so nguoi dung va cac dau vet nghiep vu quan trong tren mot man hinh.": "Theo dõi tài khoản, phân quyền, hồ sơ người dùng và các dấu vết nghiệp vụ quan trọng trên một màn hình.",
        "Quan ly tai khoan": "Quản lý tài khoản",
        "Nhat ky he thong": "Nhật ký hệ thống",
        "Ho so dang nhap": "Hồ sơ đăng nhập",
        "Dang hoat dong": "Đang hoạt động",
        "Co the dang nhap": "Có thể đăng nhập",
        "Cho xac nhan BTC": "Chờ xác nhận BTC",
        "Can admin xu ly": "Cần admin xử lý",
        "Nhat ky": "Nhật ký",
        "Dau vet he thong": "Dấu vết hệ thống",
        "Nhat ky gan day": "Nhật ký gần đây",
        "Cac thao tac moi nhat trong he thong.": "Các thao tác mới nhất trong hệ thống.",
        "dong": "dòng",
        "Thoi gian": "Thời gian",
        "Nguoi thuc hien": "Người thực hiện",
        "Hanh dong": "Hành động",
        "Bang tac dong": "Bảng tác động",
        "Chua co nhat ky he thong.": "Chưa có nhật ký hệ thống.",
        "Phan bo tai khoan": "Phân bổ tài khoản",
        "So tai khoan theo vai tro.": "Số tài khoản theo vai trò.",
        "Vai tro": "Vai trò",
        "Tao, khoa va cap nhat vai tro.": "Tạo, khóa và cập nhật vai trò.",
        "Quan ly ho so nguoi dung.": "Quản lý hồ sơ người dùng.",
        "Kiem tra dau vet nghiep vu.": "Kiểm tra dấu vết nghiệp vụ.",
        "Duyet thay doi thong tin ban to chuc.": "Duyệt thay đổi thông tin ban tổ chức.",

        "Trang chu ban to chuc": "Trang chủ ban tổ chức",
        "Tong quan giai dau, lich thi dau, doi tham gia va cac nghiep vu can xu ly.": "Tổng quan giải đấu, lịch thi đấu, đội tham gia và các nghiệp vụ cần xử lý.",
        "Dieu phoi giai dau chuyen nghiep tren mot man hinh": "Điều phối giải đấu chuyên nghiệp trên một màn hình",
        "Quan ly giai dau, san dau, lich thi dau, trong tai, ket qua va bang xep hang theo dung quy trinh van hanh.": "Quản lý giải đấu, sân đấu, lịch thi đấu, trọng tài, kết quả và bảng xếp hạng theo đúng quy trình vận hành.",
        "Quan ly lich thi dau": "Quản lý lịch thi đấu",
        "Cong bo ket qua": "Công bố kết quả",
        "Thuoc ban to chuc": "Thuộc ban tổ chức",
        "Doi tham gia": "Đội tham gia",
        "Da duyet dang ky": "Đã duyệt đăng ký",
        "Tran dau": "Trận đấu",
        "Trong cac giai": "Trong các giải",
        "Khieu nai cho xu ly": "Khiếu nại chờ xử lý",
        "Can tiep nhan/xu ly": "Cần tiếp nhận/xử lý",
        "Lich thi dau gan nhat": "Lịch thi đấu gần nhất",
        "Cac tran cua giai dau do ban to chuc quan ly.": "Các trận của giải đấu do ban tổ chức quản lý.",
        "tran": "trận",
        "Trang thai": "Trạng thái",
        "Chua co lich thi dau.": "Chưa có lịch thi đấu.",
        "Bang xep hang nhanh": "Bảng xếp hạng nhanh",
        "Du lieu tu bang xep hang da cong bo moi nhat.": "Dữ liệu từ bảng xếp hạng đã công bố mới nhất.",
        "Chua co bang xep hang da cong bo.": "Chưa có bảng xếp hạng đã công bố.",
        "Tao, cap nhat va cong bo giai dau.": "Tạo, cập nhật và công bố giải đấu.",
        "Lap bang dau, tran dau, thoi gian va san.": "Lập bảng đấu, trận đấu, thời gian và sân.",
        "Dieu chinh va cong bo ket qua.": "Điều chỉnh và công bố kết quả.",
        "Tao va cong bo bang xep hang.": "Tạo và công bố bảng xếp hạng.",

        "Trang chu trong tai": "Trang chủ trọng tài",
        "Theo doi lich phan cong, xac nhan tham gia, giam sat tran dau va bao cao su co.": "Theo dõi lịch phân công, xác nhận tham gia, giám sát trận đấu và báo cáo sự cố.",
        "San sang dieu hanh tran dau dung lich, dung vai tro": "Sẵn sàng điều hành trận đấu đúng lịch, đúng vai trò",
        "Xem cac tran duoc phan cong, tinh trang xac nhan, thong tin san dau va cac nghiep vu giam sat dang cho thuc hien.": "Xem các trận được phân công, tình trạng xác nhận, thông tin sân đấu và các nghiệp vụ giám sát đang chờ thực hiện.",
        "Tong phan cong": "Tổng phân công",
        "Tat ca trang thai": "Tất cả trạng thái",
        "Sap dien ra": "Sắp diễn ra",
        "Can theo doi": "Cần theo dõi",
        "Cho xac nhan": "Chờ xác nhận",
        "Can phan hoi": "Cần phản hồi",
        "Da gui": "Đã gửi",
        "Lich phan cong gan nhat": "Lịch phân công gần nhất",
        "Cac tran trong tai duoc phan cong.": "Các trận trọng tài được phân công.",
        "muc": "mục",
        "Tran sap phu trach": "Trận sắp phụ trách",
        "Thong tin nhanh de trong tai chuan bi.": "Thông tin nhanh để trọng tài chuẩn bị.",
        "Gan nhat": "Gần nhất",
        "Trong": "Trống",
        "Chua co phan cong tran dau.": "Chưa có phân công trận đấu.",
        "Chua co tran sap dien ra.": "Chưa có trận sắp diễn ra.",
        "Xem va phan hoi phan cong.": "Xem và phản hồi phân công.",
        "Bat dau, tam dung, tiep tuc, ket thuc tran.": "Bắt đầu, tạm dừng, tiếp tục, kết thúc trận.",
        "Gui bao cao su co phat sinh.": "Gửi báo cáo sự cố phát sinh.",
        "Tao va theo doi don nghi phep.": "Tạo và theo dõi đơn nghỉ phép.",

        "Trang chu huan luyen vien": "Trang chủ huấn luyện viên",
        "Quan ly doi bong, thanh vien, doi hinh, dang ky giai va lich thi dau cua doi.": "Quản lý đội bóng, thành viên, đội hình, đăng ký giải và lịch thi đấu của đội.",
        "Quan ly doi bong, nhan su va lich thi dau tap trung": "Quản lý đội bóng, nhân sự và lịch thi đấu tập trung",
        "Theo doi tinh trang doi bong, so luong thanh vien, lich thi dau sap toi va cac yeu cau can HLV xu ly.": "Theo dõi tình trạng đội bóng, số lượng thành viên, lịch thi đấu sắp tới và các yêu cầu cần HLV xử lý.",
        "Doi hinh thi dau": "Đội hình thi đấu",
        "Thuoc HLV": "Thuộc HLV",
        "Thanh vien": "Thành viên",
        "Dang tham gia": "Đang tham gia",
        "Da tao": "Đã tạo",
        "Lich thi dau cua doi": "Lịch thi đấu của đội",
        "Chua co doi bong.": "Chưa có đội bóng.",
        "Doi thu": "Đối thủ",
        "Chua co lich thi dau cho doi.": "Chưa có lịch thi đấu cho đội.",
        "Bang xep hang lien quan": "Bảng xếp hạng liên quan",
        "Top doi trong giai dau gan nhat cua doi.": "Top đội trong giải đấu gần nhất của đội.",
        "Chua co bang xep hang lien quan.": "Chưa có bảng xếp hạng liên quan.",
        "Tao tai khoan van dong vien.": "Tạo tài khoản vận động viên.",
        "Them, xoa, chuyen vai tro thanh vien.": "Thêm, xóa, chuyển vai trò thành viên.",
        "Tao va cap nhat doi hinh thi dau.": "Tạo và cập nhật đội hình thi đấu.",
        "Dang ky doi tham gia giai.": "Đăng ký đội tham gia giải.",

        "Trang chu van dong vien": "Trang chủ vận động viên",
        "Theo doi doi bong, doi hinh, lich thi dau ca nhan, thong ke va cac yeu cau ca nhan.": "Theo dõi đội bóng, đội hình, lịch thi đấu cá nhân, thống kê và các yêu cầu cá nhân.",
        "Nam ro lich thi dau va trang thai ca nhan": "Nắm rõ lịch thi đấu và trạng thái cá nhân",
        "Xem lich thi dau lien quan, thong tin doi bong dang tham gia, loi moi doi bong va thong ke thi dau cua ban.": "Xem lịch thi đấu liên quan, thông tin đội bóng đang tham gia, lời mời đội bóng và thống kê thi đấu của bạn.",
        "Lich thi dau ca nhan": "Lịch thi đấu cá nhân",
        "Thong ke ca nhan": "Thống kê cá nhân",
        "Tran lien quan": "Trận liên quan",
        "Trong lich ca nhan": "Trong lịch cá nhân",
        "Diem ghi nhan": "Điểm ghi nhận",
        "Tong diem": "Tổng điểm",
        "Loi moi cho phan hoi": "Lời mời chờ phản hồi",
        "Can xu ly": "Cần xử lý",
        "Don nghi phep": "Đơn nghỉ phép",
        "Chua co doi bong dang tham gia.": "Chưa có đội bóng đang tham gia.",
        "Ho so thi dau": "Hồ sơ thi đấu",
        "Thong tin tom tat cua van dong vien.": "Thông tin tóm tắt của vận động viên.",
        "Chua co doi": "Chưa có đội",
        "Vi tri": "Vị trí",
        "Ma VDV": "Mã VĐV",
        "Tran da co thong ke": "Trận đã có thống kê",
        "Dong y hoac tu choi loi moi.": "Đồng ý hoặc từ chối lời mời.",
        "Xem thong tin doi bong.": "Xem thông tin đội bóng.",
        "Xem chi so thi dau.": "Xem chỉ số thi đấu.",
        "Gui yeu cau nghi thi dau.": "Gửi yêu cầu nghỉ thi đấu.",

        "Tran sap dien ra": "Trận sắp diễn ra",
        "Trong tam hien tai": "Trọng tâm hiện tại",
        "Chua co du lieu noi bat.": "Chưa có dữ liệu nổi bật.",
        "Chua co du lieu.": "Chưa có dữ liệu.",
        "Chi so": "Chỉ số",
        "Du lieu chinh": "Dữ liệu chính",
        "Thong tin nhanh": "Thông tin nhanh",
        "Chuc nang nhanh": "Chức năng nhanh",
        "Chua dien ra": "Chưa diễn ra",
        "Dang dien ra": "Đang diễn ra",
        "Tam dung": "Tạm dừng",
        "Da ket thuc": "Đã kết thúc",
        "Da huy": "Đã hủy",
        "Trong tai chinh": "Trọng tài chính",
        "Trong tai phu": "Trọng tài phụ",
        "Giam sat": "Giám sát",
        "Da xac nhan": "Đã xác nhận",
        "Tu choi": "Từ chối"
    };

    const viToEn = {
        "Hệ thống quản lý giải đấu": "Tournament management system",
        "Tổng quan hệ thống": "System overview",
        "Màn hình điều hướng ban đầu theo vai trò người dùng.": "Initial role-based navigation screen.",
        "Tổng quan": "Overview",
        "Quản trị hệ thống": "System administration",
        "Quản lý": "Management",
        "Quản lý tài khoản, vai trò, cấu hình và nhật ký.": "Manage accounts, roles, configuration, and logs.",
        "Nghiệp vụ": "Operations",
        "Cá nhân": "Personal",
        "Trang chủ": "Home",
        "Tài khoản": "Accounts",
        "Người dùng": "Users",
        "Nhật ký hệ thống": "System logs",
        "Xác nhận thông tin BTC": "Organizer approvals",
        "Giải đấu": "Tournaments",
        "Lịch thi đấu": "Schedule",
        "Đội bóng": "Teams",
        "Sân đấu": "Venues",
        "Trọng tài": "Referees",
        "Huấn luyện viên": "Coaches",
        "Vận động viên": "Athletes",
        "Quản lý giải đấu, điều lệ, đội bóng, lịch thi đấu và kết quả.": "Manage tournaments, rules, teams, schedules, and results.",
        "Xem phân công, ghi nhận sự kiện trận đấu và báo cáo sự cố.": "View assignments, record match events, and report incidents.",
        "Quản lý đội bóng, thành viên, đăng ký giải và đội hình.": "Manage teams, members, tournament registration, and lineups.",
        "Theo dõi hồ sơ, lịch thi đấu, lời mời và đơn nghỉ phép.": "Track profile, schedule, invitations, and leave requests.",
        "Khiếu nại": "Complaints",
        "Kết quả": "Results",
        "Xếp hạng": "Standings",
        "Bảng xếp hạng": "Standings",
        "Xác nhận thông tin": "Profile approvals",
        "Lịch phân công": "Assignments",
        "Giám sát trận đấu": "Match supervision",
        "Báo cáo sự cố": "Incident reports",
        "Xin nghỉ phép": "Leave requests",
        "Đội bóng của tôi": "My team",
        "Lịch thi đấu đội": "Team schedule",
        "Tài khoản VĐV": "Athlete accounts",
        "Thành viên đội": "Team members",
        "Đội hình": "Lineup",
        "Đăng ký giải đấu": "Tournament registration",
        "Yêu cầu VĐV": "Athlete requests",
        "Thông báo": "Notifications",
        "Lời mời đội bóng": "Team invitations",
        "Thống kê": "Statistics",
        "Hồ sơ": "Profile",
        "Nghỉ phép thi đấu": "Competition leave",
        "Đăng xuất": "Log out",
        "Cài đặt": "Settings",
        "Ngôn ngữ": "Language",
        "Đổi mật khẩu": "Change password",
        "Bảo mật tài khoản": "Account security",
        "Đổi mật khẩu đăng nhập": "Change sign-in password",
        "Tìm kiếm": "Search",
        "Đang hoạt động": "Active",
        "Cập nhật mật khẩu đăng nhập cho tài khoản hiện tại.": "Update the sign-in password for the current account.",
        "Cập nhật mật khẩu định kỳ giúp bảo vệ tài khoản và dữ liệu nghiệp vụ trong hệ thống VTMS.": "Regular password updates help protect your account and VTMS business data.",
        "Tài khoản hiện tại": "Current account",
        "Thông tin đổi mật khẩu": "Password change details",
        "Nhập mật khẩu hiện tại và mật khẩu mới để xác nhận thay đổi.": "Enter your current password and new password to confirm the change.",
        "Mật khẩu hiện tại": "Current password",
        "Mật khẩu mới": "New password",
        "Xác nhận mật khẩu mới": "Confirm new password",
        "Cập nhật mật khẩu": "Update password",
        "Quy tắc bảo mật": "Security rules",
        "Mật khẩu mới phải có ít nhất 6 ký tự.": "The new password must contain at least 6 characters.",
        "Mật khẩu mới phải khác mật khẩu hiện tại.": "The new password must be different from the current password.",
        "Hệ thống lưu lịch sử mật khẩu cũ và ghi nhật ký nghiệp vụ.": "The system stores old password history and records a business log.",
        "Vui lòng nhập mật khẩu hiện tại.": "Please enter your current password.",
        "Mật khẩu mới phải từ 6 đến 72 ký tự.": "The new password must be 6 to 72 characters long.",
        "Mật khẩu xác nhận không khớp.": "The password confirmation does not match.",
        "Không thể đổi mật khẩu.": "Unable to change password.",
        "Không thể kết nối máy chủ. Vui lòng thử lại.": "Unable to connect to the server. Please try again.",
        "Đổi mật khẩu thành công.": "Password changed successfully.",
        "Tìm trận đấu, đội bóng...": "Search matches, teams...",
        "Trận sắp diễn ra": "Upcoming match",
        "Thời gian": "Time",
        "Sân đấu": "Venue",
        "Vòng đấu": "Round",
        "Trạng thái": "Status",
        "Chức năng nhanh": "Quick actions",
        "Dữ liệu chính": "Main data",
        "Thông tin nhanh": "Quick info",
        "Trang chủ quản trị hệ thống": "System administration home",
        "Tổng quan tài khoản, người dùng, yêu cầu xác nhận và nhật ký vận hành.": "Overview of accounts, users, approvals, and operation logs.",
        "Vận hành hệ thống tập trung, rõ ràng và có kiểm soát": "Centralized, clear, and controlled system operations",
        "Theo dõi tài khoản, phân quyền, hồ sơ người dùng và các dấu vết nghiệp vụ quan trọng trên một màn hình.": "Track accounts, permissions, user profiles, and important operational traces in one screen.",
        "Trang chủ ban tổ chức": "Organizer home",
        "Tổng quan giải đấu, lịch thi đấu, đội tham gia và các nghiệp vụ cần xử lý.": "Overview of tournaments, schedules, teams, and pending operations.",
        "Điều phối giải đấu chuyên nghiệp trên một màn hình": "Professional tournament coordination in one screen",
        "Quản lý giải đấu, sân đấu, lịch thi đấu, trọng tài, kết quả và bảng xếp hạng theo đúng quy trình vận hành.": "Manage tournaments, venues, schedules, referees, results, and standings through the operating workflow.",
        "Trang chủ trọng tài": "Referee home",
        "Theo dõi lịch phân công, xác nhận tham gia, giám sát trận đấu và báo cáo sự cố.": "Track assignments, confirmations, match supervision, and incident reports.",
        "Sẵn sàng điều hành trận đấu đúng lịch, đúng vai trò": "Ready to officiate matches on time and in the right role",
        "Trang chủ huấn luyện viên": "Coach home",
        "Quản lý đội bóng, thành viên, đội hình, đăng ký giải và lịch thi đấu của đội.": "Manage team, members, lineup, tournament registration, and team schedule.",
        "Quản lý đội bóng, nhân sự và lịch thi đấu tập trung": "Centralized team, roster, and schedule management",
        "Trang chủ vận động viên": "Athlete home",
        "Theo dõi đội bóng, đội hình, lịch thi đấu cá nhân, thống kê và các yêu cầu cá nhân.": "Track team, lineup, personal schedule, statistics, and personal requests.",
        "Nắm rõ lịch thi đấu và trạng thái cá nhân": "Stay on top of schedule and personal status",
        "Trang chủ khán giả": "Audience home",
        "Theo dõi đội bóng, lịch thi đấu, kết quả và bảng xếp hạng đã công bố.": "Follow published teams, schedules, results, and standings.",
        "Theo dõi thông tin giải đấu công khai": "Follow public tournament information",
        "Tất cả đội bóng, lịch thi đấu, kết quả và bảng xếp hạng được hiển thị sau khi ban tổ chức công bố.": "Teams, schedules, results, and standings are shown after organizer publication.",
        "Chưa có dữ liệu.": "No data available.",
        "Chưa có dữ liệu nổi bật.": "No highlighted data available.",
        "Chưa có bảng xếp hạng.": "No standings available.",
        "Chưa có lịch thi đấu.": "No schedule available.",
        "Chưa có lịch thi đấu công khai.": "No public schedule available.",
        "Chưa có bảng xếp hạng công khai.": "No public standings available.",
        "Chưa có bảng xếp hạng đã công bố.": "No published standings available.",
        "Chưa có phân công trận đấu.": "No match assignments available.",
        "Chưa có trận sắp diễn ra.": "No upcoming match available.",
        "Chưa có đội bóng.": "No team available.",
        "Chưa có lịch thi đấu cho đội.": "No team schedule available.",
        "Chưa có đội bóng đang tham gia.": "No active team.",
        "Chưa có lịch thi đấu cá nhân.": "No personal schedule available."
    };

    const enToVi = Object.fromEntries(Object.entries(viToEn).map(([vi, en]) => [en, vi]));
    const originalTextNodes = new WeakMap();
    const originalAttributes = new WeakMap();

    function sortedEntries(map) {
        return Object.entries(map).sort((a, b) => b[0].length - a[0].length);
    }

    const accentEntries = sortedEntries(accentMap);
    const enEntries = sortedEntries(viToEn);
    const viEntries = sortedEntries(enToVi);

    function replaceKnownPhrases(value, entries) {
        return entries.reduce((text, [from, to]) => text.split(from).join(to), value);
    }

    function toVietnamese(value) {
        return replaceKnownPhrases(value, accentEntries);
    }

    function translate(value, language) {
        const vietnamese = toVietnamese(value);

        if (language === "en") {
            return replaceKnownPhrases(vietnamese, enEntries);
        }

        return replaceKnownPhrases(vietnamese, viEntries);
    }

    function shouldSkipNode(node) {
        const parent = node.parentElement;

        if (!parent) {
            return true;
        }

        return ["SCRIPT", "STYLE", "NOSCRIPT", "TEXTAREA", "INPUT", "SELECT"].includes(parent.tagName);
    }

    function localizeTextNodes(language) {
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodes = [];

        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        nodes.forEach((node) => {
            if (shouldSkipNode(node) || node.nodeValue.trim() === "") {
                return;
            }

            if (!originalTextNodes.has(node)) {
                originalTextNodes.set(node, node.nodeValue);
            }

            const nextValue = translate(originalTextNodes.get(node), language);
            if (node.nodeValue !== nextValue) {
                node.nodeValue = nextValue;
            }
        });
    }

    function localizeAttribute(element, attributeName, language) {
        let attributes = originalAttributes.get(element);

        if (!attributes) {
            attributes = {};
            originalAttributes.set(element, attributes);
        }

        if (attributes[attributeName] === undefined) {
            attributes[attributeName] = element.getAttribute(attributeName) || "";
        }

        const nextValue = translate(attributes[attributeName], language);
        if (element.getAttribute(attributeName) !== nextValue) {
            element.setAttribute(attributeName, nextValue);
        }
    }

    function localizeAttributes(language) {
        document.querySelectorAll("[placeholder]").forEach((element) => localizeAttribute(element, "placeholder", language));
        document.querySelectorAll("[title]").forEach((element) => localizeAttribute(element, "title", language));
        document.querySelectorAll("[aria-label]").forEach((element) => localizeAttribute(element, "aria-label", language));
    }

    function localizeReadonlyValues(language) {
        document.querySelectorAll("input[disabled], textarea[disabled], input[readonly], textarea[readonly]").forEach((element) => {
            const nextValue = translate(element.value || "", language);
            if (element.value !== nextValue) {
                element.value = nextValue;
            }
        });
    }

    function setActiveLanguage(language) {
        document.documentElement.lang = language === "en" ? "en" : "vi";
        document.documentElement.dataset.vtmsLang = language;
        document.body.dataset.vtmsLang = language;
        document.querySelectorAll("[data-lang-option]").forEach((button) => {
            button.classList.toggle("active", button.dataset.langOption === language);
        });
    }

    function applyLanguage(language) {
        const nextLanguage = language === "en" ? "en" : DEFAULT_LANGUAGE;
        localStorage.setItem(STORAGE_KEY, nextLanguage);
        setActiveLanguage(nextLanguage);
        localizeTextNodes(nextLanguage);
        localizeAttributes(nextLanguage);
        localizeReadonlyValues(nextLanguage);
    }

    function initSettingsPanel() {
        const toggle = document.querySelector("[data-settings-toggle]");
        const panel = document.querySelector("[data-settings-panel]");

        toggle?.addEventListener("click", () => {
            panel?.classList.toggle("open");
            toggle.classList.toggle("active");
        });

        document.querySelectorAll("[data-lang-option]").forEach((button) => {
            button.addEventListener("click", () => applyLanguage(button.dataset.langOption || DEFAULT_LANGUAGE));
        });
    }

    let observerTimer = 0;

    function observeDynamicContent() {
        const target = document.querySelector("[data-app-content]");

        if (!target) {
            return;
        }

        const observer = new MutationObserver(() => {
            window.clearTimeout(observerTimer);
            observerTimer = window.setTimeout(() => {
                applyLanguage(localStorage.getItem(STORAGE_KEY) || DEFAULT_LANGUAGE);
            }, 80);
        });

        observer.observe(target, {
            attributes: true,
            attributeFilter: ["class", "placeholder", "title", "aria-label", "value"],
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    window.VTMSI18n = { applyLanguage };

    initSettingsPanel();
    applyLanguage(localStorage.getItem(STORAGE_KEY) || DEFAULT_LANGUAGE);
    observeDynamicContent();
})();
