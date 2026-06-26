<?php
declare(strict_types=1);

require_once __DIR__ . '/landing-blocks.php';

function admin_landing_page_keys(): array
{
    return ['about', 'products', 'career', 'news', 'claimReviews'];
}

function admin_landing_page_meta(): array
{
    return [
        'about' => [
            'label' => 'เกี่ยวกับเรา',
            'file' => 'about.html',
            'breadcrumb' => 'เกี่ยวกับเรา',
            'editor' => 'richtext',
        ],
        'products' => [
            'label' => 'บทความ',
            'file' => 'products.html',
            'breadcrumb' => 'ผลิตภัณฑ์และบริการ',
        ],
        'career' => [
            'label' => 'แนะนำอาชีพ',
            'file' => 'career.html',
            'breadcrumb' => 'แนะนำอาชีพ',
        ],
        'news' => [
            'label' => 'ข่าว/กิจกรรม',
            'file' => 'news.html',
            'breadcrumb' => 'ข่าวสารและกิจกรรม',
        ],
        'claimReviews' => [
            'label' => 'รีวิวเคลม',
            'file' => 'claim-reviews.html',
            'breadcrumb' => 'รีวิวเคลม',
        ],
    ];
}

function admin_landing_section_catalog(): array
{
    return admin_landing_block_catalog();
}

function admin_default_landing_page(string $key): array
{
    $meta = admin_landing_page_meta()[$key] ?? ['label' => $key, 'breadcrumb' => $key];

    $defaults = [
        'about' => [
            'hero' => [
                'breadcrumb' => 'เกี่ยวกับเรา',
                'title' => 'เกี่ยวกับเรา',
                'lead' => 'ที่ปรึกษาทางการเงินและประกันชีวิตที่มุ่งเน้นประโยชน์ของลูกค้าเป็นหลัก',
            ],
            'sections' => [
                [
                    'type' => 'prose',
                    'alt' => false,
                    'blocks' => [
                        ['type' => 'quote', 'html' => 'ทำงานด้วยใจรักและความเข้าใจ แน้นประโยชน์และความจำเป็นของลูกค้า อันดับ 1 เป็นที่ปรึกษาทางการเงินและประกันชีวิต ดูแลช่วยเหลือ ติดต่อได้ 24 ชั่วโมง'],
                        ['type' => 'text', 'html' => 'วรชาติ โตเต็ม ดำรงตำแหน่งผู้บริหารศูนย์ สาขานครปฐม ภายใต้ บริษัท ไทยประกันชีวิต จำกัด (มหาชน) — บริษัทประกันชีวิตแห่งแรกของคนไทย มุ่งมั่นสร้างสรรค์ผลิตภัณฑ์ด้านการประกันและการวางแผนทางการเงินให้เหมาะสมกับทุกช่วงชีวิต'],
                    ],
                ],
                ['type' => 'profile', 'alt' => false],
                [
                    'type' => 'achievements',
                    'alt' => true,
                    'heading' => 'เกียรติประวัติและผลงาน',
                    'subtitle' => 'รางวัลและความสำเร็จระดับสากล',
                    'tags' => [
                        'มาเก้า (Million Dollar Round Table)',
                        'นักขายเงินล้านระดับสากล ปี 2561–2567',
                        'นักขายเงินล้านระดับสากล',
                        'นักขายเงินล้านระดับสากล (ต่อเนื่อง)',
                    ],
                    'footer' => 'การได้รับรางวัล MDRT และนักขายเงินล้านระดับสากลสะท้อนถึงมาตรฐานการบริการและความไว้วางใจจากลูกค้าในระยะยาว',
                ],
                [
                    'type' => 'infoBlocks',
                    'alt' => true,
                    'heading' => 'บริการที่ให้คำปรึกษา',
                    'icon' => 'grid',
                    'items' => [
                        ['title' => 'แผนประกันชีวิตและสุขภาพ', 'text' => 'ออม เกษียณ ลดหย่อนภาษี สุขภาพวัยทำงาน/เด็ก Money Fit ยูนิเวอร์แซลไลฟ์ — จำลองเบี้ยและเปรียบเทียบแผน', 'href' => 'plans.html'],
                        ['title' => 'วางแผนภาษีและมรดก', 'text' => 'จัดสรรวงเงินลดหย่อนประกันชีวิตและประกันสุขภาพให้ครบตามกฎหมาย', 'href' => ''],
                        ['title' => 'บริการหลังการขาย', 'text' => 'ดูแลกรมธรรม์ เคลม เปลี่ยนแปลงผู้รับประโยชน์ — ติดต่อได้ 24 ชั่วโมง', 'href' => ''],
                        ['title' => 'รับสมัครตัวแทน', 'text' => 'สร้างอาชีพที่ 2 ภายใต้ศูนย์นครปฐม พร้อมกิจกรรมและการฝึกอบรม', 'href' => 'career.html'],
                    ],
                ],
                [
                    'type' => 'team',
                    'alt' => false,
                    'heading' => 'ทีมงานของเรา',
                    'subtitle' => 'ทีมที่พร้อมให้คำปรึกษาและดูแลลูกค้า',
                    'text' => 'ศูนย์บริการนครปฐมมีทีมที่ปรึกษาประกันชีวิตพร้อมให้บริการด้านการวางแผน การออม การลดหย่อนภาษี และการคุ้มครองครอบครัว',
                    'avatars' => ['ว', '+1'],
                ],
            ],
            'cta' => [
                'title' => 'พร้อมให้คำปรึกษา',
                'lead' => 'ติดต่อเพื่อวางแผนทางการเงินและประกันชีวิตที่เหมาะกับคุณ',
                'buttons' => [
                    ['label' => 'ติดต่อสอบถาม', 'href' => 'contact.html', 'variant' => 'primary'],
                    ['label' => 'โทร 085-292-5320', 'href' => 'tel:0852925320', 'variant' => 'outline'],
                ],
            ],
        ],
        'products' => [
            'hero' => [
                'breadcrumb' => 'ผลิตภัณฑ์และบริการ',
                'title' => 'ผลิตภัณฑ์และบริการ',
                'lead' => '“ไทยประกันชีวิต” บริษัทประกันชีวิตแห่งแรกของคนไทย มุ่งมั่นสร้างสรรค์ผลิตภัณฑ์ด้านการประกัน',
            ],
            'sections' => [
                ['type' => 'cardGrid', 'alt' => false, 'source' => 'articles'],
            ],
            'cta' => [
                'title' => 'สนใจทำประกัน?',
                'lead' => 'ปรึกษาแผนที่เหมาะกับเป้าหมายของคุณฟรี',
                'buttons' => [
                    ['label' => 'ติดต่อสอบถาม', 'href' => 'contact.html', 'variant' => 'primary'],
                ],
            ],
        ],
        'career' => [
            'hero' => [
                'breadcrumb' => 'แนะนำอาชีพ',
                'title' => 'แนะนำอาชีพ',
                'lead' => 'เริ่มต้นอาชีพที่ 2 ด้วยความมั่นใจ — งานที่เปิดโอกาสสร้างรายได้และอิสระ',
            ],
            'sections' => [
                ['type' => 'cardGrid', 'alt' => false, 'source' => 'careers'],
                [
                    'type' => 'featured',
                    'alt' => true,
                    'title' => 'สำนักงานตัวแทนแม็ก (Digital Agent)',
                    'subtitle' => 'ระบบสำนักงานตัวแทนดิจิทัลของไทยประกันชีวิต ช่วยให้ทำงานและบริหารลูกค้าได้อย่างมีประสิทธิภาพ',
                    'featureMeta' => 'Digital Agent',
                    'featureTitle' => 'สำนักงานตัวแทนแม็ก — ระบบดิจิทัลของไทยประกันชีวิต',
                    'description' => 'ทำความรู้จักระบบสำนักงานตัวแทนดิจิทัล (Digital Agent) ของไทยประกันชีวิต',
                    'slug' => 'digital-agent-system',
                    'buttonText' => 'อ่านรายละเอียด →',
                    'buttonLink' => 'careers/digital-agent-system.html',
                    'bullets' => [
                        'แอป iService และระบบเสนอแผนออนไลน์',
                        'Thai Life Academy — อบรมต่อเนื่อง',
                        'ดูแลโดยผู้บริหารศูนย์นครปฐม',
                    ],
                ],
            ],
            'cta' => [
                'title' => 'สนใจเป็นตัวแทน?',
                'lead' => 'ติดต่อเพื่อสอบถามรายละเอียดการสมัครและโอกาสในพื้นที่นครปฐม',
                'buttons' => [
                    ['label' => 'สนใจเป็นตัวแทน', 'href' => 'contact.html?topic=agent', 'variant' => 'primary'],
                    ['label' => 'โทร 085-292-5320', 'href' => 'tel:0852925320', 'variant' => 'outline'],
                ],
            ],
        ],
        'news' => [
            'hero' => [
                'breadcrumb' => 'ข่าวสารและกิจกรรม',
                'title' => 'ข่าวสารและกิจกรรม',
                'lead' => 'อัปเดตข่าว กิจกรรม และข้อมูลสำคัญจากไทยประกันชีวิต',
            ],
            'sections' => [
                ['type' => 'cardGrid', 'alt' => false, 'source' => 'news'],
                [
                    'type' => 'socialLinks',
                    'alt' => true,
                    'heading' => 'ติดตามไทยประกันชีวิต',
                    'icon' => 'share',
                    'items' => [
                        ['title' => 'Facebook @thailifepage'],
                        ['title' => 'Line @thailifeinsurance'],
                        ['title' => 'Youtube THAILIFECHANNEL'],
                    ],
                    'linkText' => 'เยี่ยมชม thailife.com →',
                    'linkHref' => 'https://www.thailife.com',
                ],
            ],
            'cta' => [
                'title' => 'สอบถามข่าวหรือกิจกรรม',
                'lead' => 'ติดต่อทีมงานเพื่อรับข้อมูลกิจกรรมในพื้นที่นครปฐม',
                'buttons' => [
                    ['label' => 'ติดต่อสอบถาม', 'href' => 'contact.html', 'variant' => 'primary'],
                ],
            ],
        ],
        'claimReviews' => [
            'hero' => [
                'breadcrumb' => 'รีวิวเคลม',
                'title' => 'รีวิวการเคลม',
                'lead' => 'ภาพและประสบการณ์จริงจากลูกค้า — เลื่อนดูการ์ดรีวิวและแกลเลอรี่การเคลม',
            ],
            'sections' => [
                ['type' => 'claimWidget', 'alt' => false],
                [
                    'type' => 'serviceCards',
                    'alt' => true,
                    'heading' => 'ช่องทางเคลมและบริการลูกค้า',
                    'icon' => 'heart',
                    'items' => [
                        ['meta' => 'ออนไลน์', 'title' => 'ไทยประกันชีวิต iService', 'text' => 'ยื่นเคลมผู้ป่วยนอก ตรวจสถานะ และดูเอกสารกรมธรรม์ผ่านแอปมือถือ', 'href' => 'https://www.thailife.com', 'linkText' => 'ใช้บริการ →'],
                        ['meta' => 'สายด่วน', 'title' => 'ฮอตไลน์ 1124', 'text' => 'สอบถามสิทธิ์เคลมและบริการลูกค้า 24 ชั่วโมง', 'href' => 'tel:1124', 'linkText' => 'โทร 1124 →'],
                        ['meta' => 'สุขภาพ', 'title' => 'เมดิแคร์ / ฮอตเคลม', 'text' => 'ใช้สิทธิ์เครือข่ายโรงพยาบาล ไม่ต้องสำรองจ่ายเต็มจำนวน', 'href' => 'https://www.thailife.com', 'linkText' => 'ดูรายละเอียด →'],
                    ],
                ],
            ],
            'cta' => [
                'title' => 'ต้องการความช่วยเหลือเรื่องเคลม?',
                'lead' => 'ติดต่อทีม Max Thai Life เพื่อสอบถามขั้นตอนเคลมและเอกสารที่ต้องใช้ — ไม่มีค่าใช้จ่ายในการสอบถาม',
                'buttons' => [
                    ['label' => 'สอบถามเรื่องเคลม', 'href' => 'contact.html', 'variant' => 'primary'],
                    ['label' => 'โทร 085-292-5320', 'href' => 'tel:0852925320', 'variant' => 'outline'],
                ],
            ],
        ],
    ];

    return $defaults[$key] ?? [
        'hero' => [
            'breadcrumb' => $meta['breadcrumb'],
            'title' => $meta['label'],
            'lead' => '',
        ],
        'sections' => [],
        'cta' => ['title' => '', 'lead' => '', 'buttons' => []],
    ];
}

function admin_normalize_landing_page(string $key, array $page): array
{
    $default = admin_default_landing_page($key);

    if (!isset($page['hero']) || !is_array($page['hero'])) {
        $page['hero'] = [
            'breadcrumb' => $default['hero']['breadcrumb'],
            'title' => (string) ($page['title'] ?? $default['hero']['title']),
            'lead' => (string) ($page['lead'] ?? $default['hero']['lead']),
        ];
    } else {
        $page['hero'] = array_merge($default['hero'], $page['hero']);
    }
    if (!isset($page['hero']['visible'])) {
        $page['hero']['visible'] = true;
    }

    if (empty($page['sections']) && ($key === 'about') && (!empty($page['quote']) || !empty($page['bio']))) {
        $blocks = [];
        if (!empty($page['quote'])) {
            $blocks[] = ['type' => 'quote', 'html' => (string) $page['quote']];
        }
        if (!empty($page['bio'])) {
            $blocks[] = ['type' => 'text', 'html' => (string) $page['bio']];
        }
        $page['sections'] = array_merge(
            [['type' => 'prose', 'alt' => false, 'blocks' => $blocks]],
            array_slice($default['sections'], 1)
        );
    }

    if (empty($page['sections'])) {
        $page['sections'] = $default['sections'];
    }
    $page['sections'] = array_map(static function (array $sec): array {
        if (!isset($sec['visible'])) {
            $sec['visible'] = true;
        }
        return $sec;
    }, $page['sections']);

    if (empty($page['cta'])) {
        $page['cta'] = $default['cta'];
    } else {
        $page['cta'] = array_merge($default['cta'], $page['cta']);
    }
    if (!isset($page['cta']['visible'])) {
        $page['cta']['visible'] = true;
    }

    $page['title'] = $page['hero']['title'];
    $page['lead'] = $page['hero']['lead'];

    return admin_normalize_page_blocks($page);
}

function admin_normalize_pages_data(array $data): array
{
    foreach (admin_landing_page_keys() as $key) {
        $existing = $data[$key] ?? [];
        if (!is_array($existing)) {
            $existing = [];
        }
        $data[$key] = admin_normalize_landing_page($key, $existing);
    }
    return $data;
}

function admin_landing_page_from_post(string $key, array $page): array
{
    $normalized = admin_normalize_landing_page($key, $page);
    if (!is_array($normalized['sections'] ?? null)) {
        $normalized['sections'] = [];
    }
    if (!is_array($normalized['cta'] ?? null)) {
        $normalized['cta'] = admin_default_landing_page($key)['cta'];
    }
    return $normalized;
}
