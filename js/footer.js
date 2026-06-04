(function () {
  var script = document.currentScript;
  var base = (script && script.getAttribute("data-base")) || "";
  var mount = document.getElementById("site-footer");
  if (!mount) return;

  function u(path) {
    return base + path;
  }

  mount.className = "site-footer";
  mount.setAttribute("role", "contentinfo");
  mount.innerHTML =
    '<div class="footer-top">' +
    '  <div class="footer-top-inner">' +
    '    <div class="footer-top-brand">' +
    '      <img src="' + u("images/profile/agent-profile.png") + '" alt="วรชาติ โตเต็ม" class="footer-top-avatar" width="95" height="95" loading="lazy" decoding="async">' +
    '      <div class="footer-top-brand-text">' +
    '        <p class="footer-logo-title">Max Thai Life</p>' +
    '        <p class="footer-logo-sub">สำนักงานตัวแทนดิจิทัล · ไทยประกันชีวิต</p>' +
    '        <p class="footer-tagline">ที่ปรึกษาทางการเงินและประกันชีวิต · สาขานครปฐม</p>' +
    '      </div>' +
    '    </div>' +
    '    <div class="footer-top-cta">' +
    '      <a href="' + u("contact.html") + '" class="btn btn-white">ติดต่อสอบถาม</a>' +
    '      <a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>' +
    '    </div>' +
    "  </div>" +
    "</div>" +
    '<div class="footer-main">' +
    '  <div class="footer-main-inner">' +
    '    <div class="footer-col footer-col-wide">' +
    "      <h4>สำนักงานตัวแทน</h4>" +
    "      <ul>" +
    '        <li><a href="' + u("index.html") + '">หน้าหลัก</a></li>' +
    '        <li><a href="' + u("about.html") + '">เกี่ยวกับเรา</a></li>' +
    '        <li><a href="' + u("plans.html") + '">แผนประกัน</a></li>' +
    '        <li><a href="' + u("products.html") + '">บทความ / ผลิตภัณฑ์</a></li>' +
    '        <li><a href="' + u("career.html") + '">แนะนำอาชีพ</a></li>' +
    '        <li><a href="' + u("news.html") + '">ข่าวและกิจกรรม</a></li>' +
    '        <li><a href="' + u("contact.html") + '">ติดต่อสอบถาม</a></li>' +
    "      </ul>" +
    "    </div>" +
    '    <div class="footer-col">' +
    "      <h4>แผนประกันแนะนำ</h4>" +
    "      <ul>" +
    '        <li><a href="' + u("plans/legacy-fit-retire.html") + '">เลกาซี ฟิต รีไทร์ 99/10</a></li>' +
    '        <li><a href="' + u("plans/tax-saving.html") + '">ลดหย่อนภาษี แบบสั้น</a></li>' +
    '        <li><a href="' + u("plans/health-working.html") + '">สุขภาพ วัยทำงาน</a></li>' +
    '        <li><a href="' + u("plans/health-kids.html") + '">สุขภาพเด็ก วัยซน</a></li>' +
    '        <li><a href="' + u("plans/money-fit.html") + '">Money Fit 12/6</a></li>' +
    '        <li><a href="' + u("plans/money-fit-firm.html") + '">Money Fit Firm 15/10</a></li>' +
    '        <li><a href="' + u("plans/universal-life.html") + '">ยูนิเวอร์แซลไลฟ์</a></li>' +
    '        <li><a href="' + u("plans.html") + '">ดูแผนทั้งหมด →</a></li>' +
    "      </ul>" +
    "    </div>" +
    '    <div class="footer-col">' +
    "      <h4>สนใจบริการ</h4>" +
    "      <ul>" +
    '        <li><a href="' + u("contact.html") + '?topic=insurance">สนใจทำประกันชีวิต</a></li>' +
    '        <li><a href="' + u("contact.html") + '?topic=agent">สนใจเป็นตัวแทน</a></li>' +
    '        <li><a href="' + u("contact.html") + '">ติดต่อสอบถามทั่วไป</a></li>' +
    '        <li><a href="' + u("about.html") + '#overview">เกียรติประวัติ MDRT</a></li>' +
    "      </ul>" +
    "    </div>" +
    '    <div class="footer-col">' +
    "      <h4>บริการลูกค้าไทยประกันชีวิต</h4>" +
    "      <ul>" +
    '        <li><a href="https://www.thailife.com/th/service/customer" target="_blank" rel="noopener">สิทธิพิเศษ</a></li>' +
    '        <li><a href="https://www.thailife.com" target="_blank" rel="noopener">ไทยประกันชีวิต iService</a></li>' +
    '        <li><a href="https://www.thailife.com" target="_blank" rel="noopener">แคร์เซ็นเตอร์ (CSC)</a></li>' +
    '        <li><a href="tel:1124">ฮอตไลน์ 1124</a></li>' +
    '        <li><a href="https://www.thailife.com" target="_blank" rel="noopener">เมดิแคร์ / ฮอตเคลม</a></li>' +
    '        <li><a href="https://www.thailife.com" target="_blank" rel="noopener">โรงพยาบาลคู่สัญญา</a></li>' +
    "      </ul>" +
    "    </div>" +
    '    <div class="footer-col">' +
    "      <h4>บริการตัวแทน</h4>" +
    "      <ul>" +
    '        <li><a href="https://www.thailife.com" target="_blank" rel="noopener">นักขายดิจิทัล (Digital Agent)</a></li>' +
    '        <li><a href="https://digitaloffices.thailife.com/worachat.tot" target="_blank" rel="noopener">Digital Office ต้นฉบับ</a></li>' +
    '        <li><a href="' + u("career.html") + '">สมัครเป็นตัวแทน</a></li>' +
    "      </ul>" +
    "    </div>" +
    '    <div class="footer-col footer-col-contact">' +
    "      <h4>ติดต่อตัวแทน</h4>" +
    '      <ul class="footer-contact-list">' +
    '        <li><span class="footer-label">ชื่อ</span> วรชาติ โตเต็ม</li>' +
    '        <li><span class="footer-label">ตำแหน่ง</span> ผู้บริหารศูนย์</li>' +
    '        <li><span class="footer-label">สาขา</span> นครปฐม</li>' +
    '        <li><span class="footer-label">โทร</span> <a href="tel:0852925320">085-292-5320</a></li>' +
    '        <li><span class="footer-label">ใบอนุญาต</span> 5701116295</li>' +
    "      </ul>" +
    '      <div class="footer-social">' +
    '        <a href="#" class="footer-social-link footer-social-link--facebook" aria-label="Facebook">' +
    '          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>' +
    "        </a>" +
    '        <a href="#" class="footer-social-link footer-social-link--line" aria-label="Line">' +
    '          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>' +
    "        </a>" +
    '        <a href="mailto:contact@example.com" class="footer-social-link footer-social-link--email" aria-label="Email">' +
    '          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>' +
    "        </a>" +
    "      </div>" +
    "    </div>" +
    "  </div>" +
    "</div>" +
    '<div class="footer-bottom">' +
    '  <div class="footer-bottom-inner">' +
    '    <p class="footer-copy">สงวนสิทธิ์ © ' + new Date().getFullYear() + " บริษัท ไทยประกันชีวิต จำกัด (มหาชน)</p>" +
    '    <nav class="footer-legal" aria-label="ลิงก์ทางกฎหมาย">' +
    '      <a href="https://www.thailife.com/th/privacy" target="_blank" rel="noopener">นโยบายส่วนบุคคล</a>' +
    '      <span aria-hidden="true">·</span>' +
    '      <a href="https://www.thailife.com" target="_blank" rel="noopener">thailife.com</a>' +
    '      <span aria-hidden="true">·</span>' +
    '      <a href="https://digitaloffices.thailife.com/worachat.tot" target="_blank" rel="noopener">Digital Office</a>' +
    "    </nav>" +
    "  </div>" +
    "</div>";
})();
