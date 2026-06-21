<?php
declare(strict_types=1);

/**
 * Recommended image dimensions for admin upload fields.
 * Keys are used in form field names: image_spec="hero_banner"
 */
return [
    'logo' => [
        'label' => 'โลโก้',
        'width' => 92,
        'height' => 92,
        'hint' => '92 × 92 px · PNG พื้นโปร่งใส',
        'path' => 'images/logo/LOGO-THAILIFE.png',
    ],
    'hero_banner' => [
        'label' => 'แบนเนอร์หน้าแรก',
        'width' => 1920,
        'height' => 800,
        'hint' => '1920 × 800 px · JPG/PNG · อัตราส่วน ~2.4:1',
        'path' => 'images/hero-banner.png',
    ],
    'agent_profile' => [
        'label' => 'รูปโปรไฟล์ตัวแทน',
        'width' => 400,
        'height' => 400,
        'hint' => '400 × 400 px · JPG/PNG · รูปสquare',
        'path' => 'images/profile/agent-profile.png',
    ],
    'cta_banner' => [
        'label' => 'แบนเนอร์ CTA หน้าแรก',
        'width' => 1200,
        'height' => 400,
        'hint' => '1200 × 400 px · JPG/PNG · อัตราส่วน 3:1',
        'path' => 'images/cta/home-cta-banner.png',
    ],
    'plan_cover' => [
        'label' => 'ภาพปกแผนประกัน',
        'width' => 960,
        'height' => 540,
        'hint' => '960 × 540 px · JPG/PNG · อัตราส่วน 16:9',
        'path' => 'images/cover แผนประกัน/',
    ],
    'plan_brochure' => [
        'label' => 'ภาพโบรชัวร์แผน',
        'width' => 1200,
        'height' => 1697,
        'hint' => '1200 × 1697 px · PNG/JPG · อัตราส่วน A4 แนวตั้ง',
        'path' => 'images/cover แผนประกัน/',
    ],
    'plan_content' => [
        'label' => 'รูปในเนื้อหาแผน',
        'width' => 960,
        'height' => 540,
        'hint' => '960 × 540 px · JPG/PNG · แนะนำไม่เกิน 1200 px กว้าง',
        'path' => 'images/cover แผนประกัน/',
    ],
    'media_library' => [
        'label' => 'คลังรูป',
        'width' => 1200,
        'height' => 800,
        'hint' => 'JPG, PNG, WEBP · ไม่เกิน 8 MB',
        'path' => 'images/uploads/',
    ],
    'video_library' => [
        'label' => 'คลังวิดีโอ',
        'hint' => 'MP4, WEBM, OGG, MOV · ไม่เกิน 50 MB',
        'path' => 'videos/uploads/',
        'extensions' => ['mp4', 'webm', 'ogg', 'mov'],
        'maxSize' => 50 * 1024 * 1024,
    ],
    'article_cover' => [
        'label' => 'ภาพปกบทความ/ข่าว',
        'width' => 612,
        'height' => 612,
        'hint' => '612 × 612 px · JPG · รูปสquare',
        'path' => 'images/cover cart/',
    ],
    'career_cover' => [
        'label' => 'ภาพปกแนะนำอาชีพ',
        'width' => 800,
        'height' => 450,
        'hint' => '800 × 450 px · JPG/PNG · อัตราส่วน 16:9',
        'path' => 'images/แนะนำอาชีพ/',
    ],
    'claim_cover' => [
        'label' => 'ภาพปกรีวิวเคลม',
        'width' => 612,
        'height' => 612,
        'hint' => '612 × 612 px · JPG · รูปสquare',
        'path' => 'images/cover cart/',
    ],
    'article_thumb' => [
        'label' => 'ภาพย่อบทความ (สไลด์)',
        'width' => 176,
        'height' => 176,
        'hint' => '176 × 176 px · JPG · แสดง 88×88 บนหน้าเว็บ',
        'path' => 'images/cover cart/',
    ],
    'og_image' => [
        'label' => 'ภาพแชร์โซเชียล (OG)',
        'width' => 1200,
        'height' => 630,
        'hint' => '1200 × 630 px · JPG/PNG',
        'path' => 'images/logo/LOGO-THAILIFE.png',
    ],
];
