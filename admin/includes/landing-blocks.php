<?php
declare(strict_types=1);

/** Block Builder catalog — section types for landing page editor */
function admin_landing_block_catalog(): array
{
    return [
        'heroBanner' => 'Hero Banner',
        'heading' => 'หัวข้อ',
        'text' => 'ข้อความ',
        'image' => 'รูปภาพ',
        'imageText' => 'รูป + ข้อความ',
        'cardGrid2' => 'การ์ด 2 คอลัมน์',
        'cardGrid3' => 'การ์ด 3 คอลัมน์',
        'cardGrid4' => 'การ์ด 4 คอลัมน์',
        'ctaButton' => 'ปุ่ม CTA',
        'gallery' => 'แกลเลอรี่รูป',
        'faq' => 'คำถามที่พบบ่อย (FAQ)',
        'team' => 'ทีมงาน',
        'review' => 'รีวิวลูกค้า',
        'contactInfo' => 'ข้อมูลติดต่อ',
        'video' => 'วิดีโอ',
        'customHtml' => 'HTML กำหนดเอง',
        'specTable' => 'ตารางข้อมูลแผน',
        'bulletList' => 'รายการจุดเด่น',
        // Legacy (ยังรองรับหน้าเดิม)
        'prose' => 'ข้อความ (เดิม)',
        'profile' => 'ข้อมูลตัวแทน',
        'achievements' => 'เกียรติประวัติ',
        'infoBlocks' => 'บล็อกข้อมูล (เดิม)',
        'serviceCards' => 'การ์ดบริการ (เดิม)',
        'cardGrid' => 'การ์ดรายการอัตโนมัติ',
        'featured' => 'บทความเด่น',
        'socialLinks' => 'ลิงก์โซเชียล',
        'claimWidget' => 'สไลด์รีวิวเคลม',
    ];
}

function admin_landing_block_image_hint(string $type): string
{
    $hints = [
        'heroBanner' => '1920×600 px — แบนเนอร์ Hero',
        'image' => '1200×800 px — รูปเต็มความกว้าง',
        'imageText' => '800×600 px — รูปคู่ข้อความ',
        'gallery' => '800×600 px — แกลเลอรี่',
        'cardGrid2' => '600×400 px — การ์ด',
        'cardGrid3' => '600×400 px — การ์ด',
        'cardGrid4' => '480×320 px — การ์ด',
        'team' => '400×400 px — รูปโปรไฟล์',
        'review' => '80×80 px — รูปผู้รีวิว',
    ];
    return $hints[$type] ?? '1200×800 px — JPG หรือ PNG';
}

function admin_landing_default_block_item(): array
{
    return [
        'title' => '',
        'subtitle' => '',
        'description' => '',
        'image' => ['src' => '', 'alt' => ''],
        'buttonText' => '',
        'buttonLink' => '',
        'isVisible' => true,
        'sortOrder' => 0,
    ];
}

function admin_landing_default_block(string $type): array
{
    $base = array_merge(admin_landing_default_block_item(), [
        'id' => bin2hex(random_bytes(8)),
        'type' => $type,
        'alt' => false,
        'icon' => 'shield-check',
        'showIcon' => true,
        'items' => [],
        'videoUrl' => '',
        'videoSrc' => '',
        'customHtml' => '',
        'columns' => 3,
    ]);

    $samples = [
        'heading' => ['title' => 'หัวข้อใหม่', 'subtitle' => 'คำอธิบายย่อย'],
        'text' => ['description' => 'เนื้อหาข้อความ'],
        'image' => ['title' => '', 'subtitle' => '', 'showIcon' => false, 'image' => ['src' => '', 'alt' => '']],
        'imageText' => ['title' => 'หัวข้อ', 'description' => 'รายละเอียด', 'showIcon' => false, 'image' => ['src' => '', 'alt' => '']],
        'ctaButton' => ['title' => 'พร้อมเริ่มต้น?', 'description' => 'ติดต่อเราวันนี้', 'buttonText' => 'ติดต่อสอบถาม', 'buttonLink' => 'contact.html'],
        'gallery' => ['title' => '', 'subtitle' => '', 'showIcon' => false, 'items' => [['image' => ['src' => '', 'alt' => '']]]],
        'faq' => ['title' => 'คำถามที่พบบ่อย', 'items' => [['title' => 'คำถาม?', 'description' => 'คำตอบ']]],
        'team' => ['title' => 'ทีมงาน', 'subtitle' => '', 'description' => '', 'avatars' => ['?', '+1'], 'items' => []],
        'review' => ['title' => 'รีวิวลูกค้า', 'items' => [['description' => 'ข้อความรีวิว', 'title' => 'ชื่อลูกค้า']]],
        'contactInfo' => ['title' => 'ติดต่อเรา', 'description' => 'โทร 085-292-5320'],
        'video' => ['title' => '', 'subtitle' => '', 'showIcon' => false, 'videoUrl' => '', 'videoSrc' => ''],
        'customHtml' => ['customHtml' => '<p>เนื้อหา HTML</p>'],
        'specTable' => ['showIcon' => false, 'items' => [['title' => 'หัวข้อ', 'description' => 'รายละเอียด', 'isVisible' => true]]],
        'bulletList' => ['showIcon' => false, 'items' => [['title' => 'หัวข้อ', 'description' => 'รายละเอียด', 'isVisible' => true]]],
        'socialLinks' => [
            'title' => 'ติดตามไทยประกันชีวิต',
            'icon' => 'share-2',
            'linkText' => 'เยี่ยมชมเว็บไซต์ →',
            'linkHref' => 'https://www.thailife.com',
            'items' => [
                ['title' => 'Facebook', 'subtitle' => '@thailifepage', 'icon' => 'facebook', 'buttonLink' => 'https://www.facebook.com/thailifepage', 'isVisible' => true],
                ['title' => 'Line', 'subtitle' => '@thailifeinsurance', 'icon' => 'line', 'buttonLink' => 'https://line.me/', 'isVisible' => true],
                ['title' => 'YouTube', 'subtitle' => 'THAILIFECHANNEL', 'icon' => 'youtube', 'buttonLink' => 'https://www.youtube.com/', 'isVisible' => true],
            ],
        ],
        'cardGrid2' => ['title' => 'บริการของเรา', 'columns' => 2, 'items' => [admin_landing_default_block_item()]],
        'cardGrid3' => ['title' => 'บริการของเรา', 'columns' => 3, 'items' => [admin_landing_default_block_item()]],
        'cardGrid4' => ['title' => 'บริการของเรา', 'columns' => 4, 'items' => [admin_landing_default_block_item()]],
    ];

    if (isset($samples[$type])) {
        $base = array_replace_recursive($base, $samples[$type]);
    }

    if (str_starts_with($type, 'cardGrid') && empty($base['items'])) {
        $base['items'] = [admin_landing_default_block_item()];
    }

    return $base;
}

function admin_team_items_are_placeholder(?array $items): bool
{
    if ($items === null || $items === []) {
        return true;
    }
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $src = trim((string) ($item['image']['src'] ?? ''));
        if ($src !== '') {
            return false;
        }
        $title = trim((string) ($item['title'] ?? ''));
        $subtitle = trim((string) ($item['subtitle'] ?? ''));
        if ($title !== '' && $title !== 'ชื่อ') {
            return false;
        }
        if ($subtitle !== '' && $subtitle !== 'ตำแหน่ง') {
            return false;
        }
    }

    return true;
}

function admin_normalize_block_section(array $sec, int $index = 0): array
{
    $type = (string) ($sec['type'] ?? 'text');
    $defaults = admin_landing_default_block($type);

    // Legacy field mapping
    if (!empty($sec['heading']) && empty($sec['title'])) {
        $sec['title'] = $sec['heading'];
    }
    if (!empty($sec['lead']) && empty($sec['description'])) {
        $sec['description'] = $sec['lead'];
    }
    if (!empty($sec['text']) && empty($sec['description'])) {
        $sec['description'] = $sec['text'];
    }
    if (isset($sec['visible']) && !isset($sec['isVisible'])) {
        $sec['isVisible'] = (bool) $sec['visible'];
    }
    if (!isset($sec['isVisible'])) {
        $sec['isVisible'] = true;
    }
    $sec['visible'] = $sec['isVisible'];
    $sec['sortOrder'] = $index;

    if (empty($sec['id'])) {
        $sec['id'] = $defaults['id'];
    }

    if (empty($sec['image']) || !is_array($sec['image'])) {
        $sec['image'] = ['src' => '', 'alt' => ''];
    }

    if ($type === 'team' && !empty($sec['avatars'])) {
        unset($defaults['items']);
    }

    $sec = array_merge($defaults, $sec);
    $sec['type'] = $type;
    $sec['sortOrder'] = $index;

    if (!is_array($sec['items'] ?? null)) {
        $sec['items'] = [];
    }

    if ($type === 'team' && !empty($sec['avatars']) && admin_team_items_are_placeholder($sec['items'])) {
        $sec['items'] = [];
    }

    // Legacy items mapping
    if ($type === 'achievements' && !empty($sec['tags']) && empty($sec['items'])) {
        foreach ($sec['tags'] as $i => $tag) {
            $sec['items'][] = array_merge(admin_landing_default_block_item(), [
                'title' => (string) $tag,
                'sortOrder' => $i,
            ]);
        }
    }
    if ($type === 'video') {
        $sec['showIcon'] = false;
        if (($sec['title'] ?? '') === 'วิดีโอ') {
            $sec['title'] = '';
        }
        if (($sec['subtitle'] ?? '') === 'คำอธิบายย่อย') {
            $sec['subtitle'] = '';
        }
    }

    if ($type === 'featured') {
        if (!empty($sec['heading']) && empty($sec['title'])) {
            $sec['title'] = (string) $sec['heading'];
        }
        $slug = trim((string) ($sec['slug'] ?? ''));
        if ($slug !== '') {
            $careers = json_read('careers.json');
            $item = $careers['items'][$slug] ?? null;
            if (is_array($item)) {
                if (empty($sec['featureMeta'])) {
                    $sec['featureMeta'] = (string) ($item['category'] ?? '');
                }
                if (empty($sec['featureTitle'])) {
                    $sec['featureTitle'] = (string) ($item['title'] ?? '');
                }
                if (empty($sec['description'])) {
                    $sec['description'] = (string) ($item['description'] ?? '');
                }
                if (empty($sec['image']['src'] ?? '')) {
                    $sec['image']['src'] = (string) ($item['image'] ?? '');
                    $sec['image']['alt'] = (string) ($item['title'] ?? $sec['featureTitle'] ?? '');
                }
                if (empty($sec['buttonLink'])) {
                    $sec['buttonLink'] = 'careers/' . $slug . '.html';
                }
            }
        }
        if (empty($sec['buttonText'])) {
            $sec['buttonText'] = 'อ่านรายละเอียด →';
        }
        if (!empty($sec['bullets']) && is_array($sec['bullets']) && empty($sec['items'])) {
            foreach ($sec['bullets'] as $i => $bullet) {
                $sec['items'][] = array_merge(admin_landing_default_block_item(), [
                    'title' => (string) $bullet,
                    'sortOrder' => $i,
                ]);
            }
        }
    }

    if (in_array($type, ['infoBlocks', 'serviceCards'], true) && !empty($sec['items'])) {
        foreach ($sec['items'] as $i => &$item) {
            if (!is_array($item)) {
                continue;
            }
            if (!empty($item['meta']) && empty($item['subtitle'])) {
                $item['subtitle'] = (string) $item['meta'];
            }
            if (!empty($item['text']) && empty($item['description'])) {
                $item['description'] = (string) $item['text'];
            }
            if (!empty($item['linkText']) && empty($item['buttonText'])) {
                $item['buttonText'] = (string) $item['linkText'];
            }
            if (!empty($item['href']) && empty($item['buttonLink'])) {
                $item['buttonLink'] = (string) $item['href'];
            }
            $item['sortOrder'] = $i;
        }
        unset($item);
    }

    if ($type === 'socialLinks' && !empty($sec['items'])) {
        foreach ($sec['items'] as $i => &$item) {
            if (!is_array($item)) {
                continue;
            }
            $rawTitle = trim((string) ($item['title'] ?? ''));
            if (empty($item['subtitle']) && empty($item['description']) && preg_match('/^(\S+)\s+(.+)$/u', $rawTitle, $m)) {
                $item['title'] = $m[1];
                $item['subtitle'] = $m[2];
            }
            if (empty($item['icon'])) {
                $guess = strtolower($rawTitle . ' ' . ($item['subtitle'] ?? ''));
                if (str_contains($guess, 'facebook') || str_contains($guess, 'fb')) {
                    $item['icon'] = 'facebook';
                } elseif (str_contains($guess, 'line')) {
                    $item['icon'] = 'line';
                } elseif (str_contains($guess, 'youtube')) {
                    $item['icon'] = 'youtube';
                } elseif (str_contains($guess, 'instagram')) {
                    $item['icon'] = 'instagram';
                } elseif (str_contains($guess, 'mail') || str_contains($guess, 'email')) {
                    $item['icon'] = 'mail';
                } else {
                    $item['icon'] = 'globe';
                }
            }
            $item['sortOrder'] = $i;
        }
        unset($item);
    }

    return $sec;
}

function admin_normalize_page_blocks(array $page): array
{
    if (!isset($page['hero']) || !is_array($page['hero'])) {
        $page['hero'] = [];
    }
    $hero = &$page['hero'];
    if (!isset($hero['isVisible']) && isset($hero['visible'])) {
        $hero['isVisible'] = (bool) $hero['visible'];
    }
    if (!isset($hero['isVisible'])) {
        $hero['isVisible'] = true;
    }
    $hero['visible'] = $hero['isVisible'];

    if (!is_array($page['sections'] ?? null)) {
        $page['sections'] = [];
    }
    $page['sections'] = array_values(array_map(
        static fn (array $sec, int $i): array => admin_normalize_block_section($sec, $i),
        $page['sections'],
        array_keys($page['sections'])
    ));

    if (!isset($page['cta']) || !is_array($page['cta'])) {
        $page['cta'] = [];
    }
    $cta = &$page['cta'];
    if (!isset($cta['isVisible']) && isset($cta['visible'])) {
        $cta['isVisible'] = (bool) $cta['visible'];
    }
    if (!isset($cta['isVisible'])) {
        $cta['isVisible'] = true;
    }
    $cta['visible'] = $cta['isVisible'];

    return $page;
}
