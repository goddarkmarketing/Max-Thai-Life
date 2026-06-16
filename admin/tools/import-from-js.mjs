#!/usr/bin/env node
/**
 * One-time import: reads existing js/*-data.js and writes data/*.json
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '../..');
const jsDir = path.join(root, 'js');
const dataDir = path.join(root, 'data');

async function loadJsVar(file, varName) {
  const code = fs.readFileSync(path.join(jsDir, file), 'utf8');
  const window = {};
  const fn = new Function('window', `${code}\nreturn window.${varName.replace('window.', '')};`);
  return fn(window);
}

function writeJson(name, data) {
  fs.mkdirSync(dataDir, { recursive: true });
  fs.writeFileSync(
    path.join(dataDir, name),
    JSON.stringify(data, null, 2) + '\n',
    'utf8'
  );
  console.log('  ✓', name);
}

function extractTestimonials(html) {
  const cards = [];
  const re = /class="testimonial-quote">([^<]+)<\/p>\s*<p class="testimonial-author">([^<]+)<\/p>/g;
  let m;
  while ((m = re.exec(html)) !== null) {
    cards.push({ quote: m[1].trim(), author: m[2].trim() });
  }
  const slides = [];
  for (let i = 0; i < cards.length; i += 3) {
    slides.push(cards.slice(i, i + 3));
  }
  return slides;
}

function extractHomeArticles(html) {
  const items = [];
  const re = /<li class="article-item">([\s\S]*?)<\/li>/g;
  let m;
  while ((m = re.exec(html)) !== null) {
    const block = m[1];
    const href = block.match(/href="([^"]+)"/)?.[1] || '';
    const slug = href.replace(/^articles\//, '').replace(/\.html$/, '');
    const image = block.match(/src="([^"]+)"/)?.[1] || '';
    const meta = block.match(/class="article-meta">([^<]+)/)?.[1] || '';
    const title = block.match(/<h3><a[^>]*>([^<]+)/)?.[1] || '';
    const excerpt = block.match(/class="article-excerpt">([^<]+)/)?.[1] || '';
    const views = parseInt(block.match(/(\d[\d,]*)\s*views/)?.[1]?.replace(/,/g, '') || '0', 10);
    const shares = parseInt(block.match(/(\d+)\s*shares/)?.[1] || '0', 10);
    items.push({ slug, href, image, meta, title, excerpt, views, shares });
  }
  return items;
}

async function main() {
  console.log('Importing JS data → JSON...\n');

  const articlesDetail = await loadJsVar('articles-data.js', 'window.ARTICLES_DETAIL');
  writeJson('articles.json', { items: articlesDetail });

  const plansData = await loadJsVar('plans-data.js', 'window.PLANS_DATA');
  writeJson('plans.json', { items: plansData });

  const plansDetail = await loadJsVar('plans-detail-content.js', 'window.PLANS_DETAIL');
  writeJson('plans-detail.json', { items: plansDetail });

  const newsDetail = await loadJsVar('news-data.js', 'window.NEWS_DETAIL');
  const newsList = await loadJsVar('news-data.js', 'window.NEWS_LIST');
  const newsHome = await loadJsVar('news-data.js', 'window.NEWS_HOME');
  writeJson('news.json', { list: newsList, home: newsHome, items: newsDetail });

  const careersDetail = await loadJsVar('careers-data.js', 'window.CAREERS_DETAIL');
  const careersList = await loadJsVar('careers-data.js', 'window.CAREERS_LIST');
  writeJson('careers.json', { list: careersList, items: careersDetail });

  const claimsDetail = await loadJsVar('claim-reviews-data.js', 'window.CLAIM_REVIEWS_DETAIL');
  const claimsList = await loadJsVar('claim-reviews-data.js', 'window.CLAIM_REVIEWS_LIST');
  const galleryMore = await loadJsVar('claim-reviews-data.js', 'window.CLAIM_GALLERY_MORE');
  writeJson('claim-reviews.json', {
    list: claimsList,
    galleryMore: galleryMore,
    items: claimsDetail,
  });

  const indexHtml = fs.readFileSync(path.join(root, 'index.html'), 'utf8');

  writeJson('site.json', {
    brand: {
      name: 'Max Thai Life',
      sub: 'สำนักงานตัวแทนแม็ก',
      logo: 'images/logo/LOGO-THAILIFE.png',
    },
    agent: {
      name: 'วรชาติ โตเต็ม',
      title: 'ผู้บริหารศูนย์',
      branch: 'นครปฐม',
      phone: '0852925320',
      phoneDisplay: '085-292-5320',
      license: '5701116295',
      ulRights: 'มีสิทธิ์',
      tagline: 'ที่ปรึกษาประกันชีวิตและการเงิน · สาขานครปฐม · ใบอนุญาต 5701116295',
    },
    social: {
      facebook: '#',
      line: '#',
      email: 'contact@example.com',
    },
    footer: {
      tagline: 'ที่ปรึกษาทางการเงินและประกันชีวิต · สาขานครปฐม',
      planLinks: [
        { label: 'เลกาซี ฟิต รีไทร์ 99/10', href: 'plans/legacy-fit-retire.html' },
        { label: 'ลดหย่อนภาษี แบบสั้น', href: 'plans/tax-saving.html' },
        { label: 'สุขภาพ วัยทำงาน', href: 'plans/health-working.html' },
        { label: 'สุขภาพเด็ก วัยซน', href: 'plans/health-kids.html' },
        { label: 'Money Fit 12/6', href: 'plans/money-fit.html' },
        { label: 'Money Fit Firm 15/10', href: 'plans/money-fit-firm.html' },
        { label: 'ยูนิเวอร์แซลไลฟ์', href: 'plans/universal-life.html' },
      ],
    },
    meta: {
      description:
        'Max Thai Life — ผู้บริหารศูนย์ ไทยประกันชีวิต สาขานครปฐม ที่ปรึกษาทางการเงินและประกันชีวิต',
    },
  });

  writeJson('home.json', {
    hero: {
      image: 'images/hero-banner.png',
      alt: 'Max Thai Life — ที่ปรึกษาทางการเงินและประกันชีวิต สาขานครปฐม ไทยประกันชีวิต',
      avatar: 'images/profile/agent-profile.png',
      lead: 'ที่ปรึกษาประกันชีวิตและการเงิน · สาขานครปฐม · ใบอนุญาต 5701116295',
      ctaPrimary: { label: 'ขอใบเสนอเบี้ยฟรี', href: '#inquiry' },
      ctaPhone: { label: 'โทร 085-292-5320', href: 'tel:0852925320' },
      ctaContact: { label: 'ติดต่อสอบถาม', href: 'contact.html' },
    },
    profile: {
      title: 'ข้อมูลตัวแทน',
      subtitle: 'วรชาติ โตเต็ม · สำนักงานตัวแทนแม็ก ไทยประกันชีวิต',
      fields: [
        { label: 'ตำแหน่ง', value: 'ผู้บริหารศูนย์' },
        { label: 'สาขา', value: 'นครปฐม' },
        { label: 'เบอร์ติดต่อ', value: '085-292-5320', link: 'tel:0852925320' },
        { label: 'ใบอนุญาตตัวแทน', value: '5701116295' },
        { label: 'สิทธิ์ขายยูนิเวอร์แซลไลฟ์', value: 'มีสิทธิ์' },
      ],
    },
    plansSection: {
      title: 'แผนประกันแนะนำ',
      subtitle: 'เลือกแผนตามเป้าหมาย — ดูรายละเอียดครบทุกแผนที่หน้าแผนประกัน',
      goalChips: [
        { label: 'ดูทั้ง 9 แผน', href: 'plans.html', all: true },
        { label: 'ออมและเกษียณ', href: 'plans/legacy-fit-retire.html' },
        { label: 'ลดหย่อนภาษี', href: 'plans/tax-saving.html' },
        { label: 'สุขภาพ', href: 'plans/health-working.html' },
        { label: 'ไลฟ์เวิร์ส 99/99', href: 'plans/life-wealth-fit-99-99.html' },
        { label: 'ลงทุน / UL', href: 'plans/universal-life.html' },
      ],
      planLimit: 4,
    },
    articlesSection: {
      title: 'เรื่องเด่นประเด็นร้อน',
      subtitle: 'บทความและข้อมูลที่น่าสนใจจากไทยประกันชีวิต',
      items: extractHomeArticles(indexHtml),
    },
    testimonialsSection: {
      title: 'เสียงจากลูกค้า',
      subtitle: 'ประสบการณ์จริงจากผู้ที่วางแผนไปกับเรา',
      slides: extractTestimonials(indexHtml),
    },
    newsSection: {
      title: 'ข่าวและกิจกรรม',
      subtitle: 'อัปเดตล่าสุดจากไทยประกันชีวิตและทีมงาน',
    },
    inquiry: {
      title: 'ขอใบเสนอเบี้ยฟรี',
      subtitle: 'กรอกข้อมูลสั้นๆ เราจะติดต่อกลับเพื่อให้คำปรึกษา — ไม่มีค่าใช้จ่าย ไม่เร่งขาย',
      points: [
        'ปรึกษาและออกใบเสนอเบี้ยฟรี ไม่มีค่าใช้จ่าย',
        'ติดต่อกลับภายใน 1 วันทำการ',
        'หรือโทรเลย 085-292-5320',
      ],
      formNote: 'ข้อมูลของคุณจะใช้เพื่อติดต่อกลับเท่านั้น · แบบฟอร์มตัวอย่างสำหรับเว็บไซต์ท้องถิ่น',
    },
    ctaBanner: {
      image: 'images/cta/home-cta-banner.png',
      alt: 'พร้อมวางแผนประกันชีวิตที่เหมาะกับคุณ — วางแผนวันนี้ เพื่ออนาคตที่มั่นคง',
      href: '#inquiry',
    },
  });

  writeJson('pages.json', {
    about: {
      title: 'เกี่ยวกับเรา',
      lead: 'ที่ปรึกษาทางการเงินและประกันชีวิตที่มุ่งเน้นประโยชน์ของลูกค้าเป็นหลัก',
      quote:
        'ทำงานด้วยใจรักและความเข้าใจ แน้นประโยชน์และความจำเป็นของลูกค้า อันดับ 1 เป็นที่ปรึกษาทางการเงินและประกันชีวิต ดูแลช่วยเหลือ ติดต่อได้ 24 ชั่วโมง',
      bio: 'วรชาติ โตเต็ม ดำรงตำแหน่งผู้บริหารศูนย์ สาขานครปฐม ภายใต้ บริษัท ไทยประกันชีวิต จำกัด (มหาชน) — บริษัทประกันชีวิตแห่งแรกของคนไทย มุ่งมั่นสร้างสรรค์ผลิตภัณฑ์ด้านการประกันและการวางแผนทางการเงินให้เหมาะสมกับทุกช่วงชีวิต',
    },
    contact: {
      title: 'ติดต่อสอบถาม',
      lead: 'พร้อมให้คำปรึกษาเรื่องประกันชีวิต การออม และการวางแผนการเงิน',
    },
    plans: {
      title: 'แผนประกัน',
      lead: 'เลือกแผนที่ตรงเป้าหมาย — ออม เกษียณ ลดหย่อนภาษี ดูแลสุขภาพ และวางแผนมรดก จากไทยประกันชีวิต',
      categories: [
        { filter: 'all', label: 'ทั้งหมด' },
        { filter: 'savings', label: 'ออมทรัพย์' },
        { filter: 'protect', label: 'คุ้มครองชีวิต' },
        { filter: 'health', label: 'ประกันสุขภาพ' },
        { filter: 'rider', label: 'สัญญาเพิ่มเติม' },
        { filter: 'pension', label: 'บำนาญ/เกษียณ' },
        { filter: 'invest', label: 'ลงทุน/Life Verse' },
      ],
    },
  });

  console.log('\nDone. Run admin publish to regenerate JS files.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
