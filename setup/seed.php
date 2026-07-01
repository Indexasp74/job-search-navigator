<?php
require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/auth.php';
require_once dirname(__DIR__) . '/inc/db.php';

$token = $_GET['token'] ?? '';
if (!TRACKER_TOKEN || !hash_equals(TRACKER_TOKEN, $token)) {
    http_response_code(401);
    exit('Unauthorized');
}

$seed = [
    ['company'=>'Acme Corp','role'=>'Senior Product Designer','date'=>'2026-06-15','status'=>'active','fit'=>'high','resume'=>'resume_acme.pdf','notes'=>'Strong cultural fit, pending hiring manager review.'],
    ['company'=>'Tech Innovations Inc','role'=>'Design Operations Manager','date'=>'2026-06-10','status'=>'screen','fit'=>'high','resume'=>'resume_tech.pdf','notes'=>'Phone screen scheduled for next week.'],
    ['company'=>'Global Solutions Ltd','role'=>'UX Lead','date'=>'2026-06-01','status'=>'no','fit'=>'med','resume'=>'resume_global.pdf','notes'=>'Position filled internally.'],
    ['company'=>'Startup Ventures','role'=>'Principal Design Manager','date'=>'2026-05-25','status'=>'active','fit'=>'high','resume'=>'resume_startup.pdf','notes'=>'Early-stage company, mission-driven.'],
    ['company'=>'Enterprise Systems','role'=>'Director of User Experience','date'=>'2026-05-20','status'=>'interview','fit'=>'high','resume'=>'resume_enterprise.pdf','notes'=>'Second round interview with design leadership team.'],
    ['company'=>'Humana','role'=>'Director Product Design — Enterprise Digital','date'=>'2026-04-08','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_Humana.docx','notes'=>'Not selected. All three Humana roles closed.'],
    ['company'=>'Pilot Company','role'=>'Senior Manager, Learning','date'=>'2026-04-10','status'=>'screen','fit'=>'med','resume'=>'Richard_Lee_Resume_Pilot.docx','notes'=>'Phone screen with Shayna Barbee complete — positive. Article outreach sent.','local'=>true],
    ['company'=>'Tivity Health','role'=>'Director of Product Design','date'=>'2026-04-17','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Tivity.docx','notes'=>'Lifelong fitness user. SilverSneakers UX for older adults.'],
    ['company'=>'Dandy','role'=>'Senior Product Design Manager','date'=>'2026-04-17','status'=>'canceled','fit'=>'high','resume'=>'Richard_Lee_Resume_Dandy.docx','notes'=>'Role paused by Dandy.'],
    ['company'=>'Cirrus Aircraft','role'=>'Manager, IT Product','date'=>'2026-04-17','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_Cirrus.docx','notes'=>'Rejected by Talent Acquisition.','local'=>true],
    ['company'=>'Cirrus Aircraft','role'=>'Product Owner, Learning Systems','date'=>'2026-06-03','status'=>'canceled','fit'=>'med','resume'=>'','notes'=>'Local Alcoa TN bridge role. Role no longer available when assessed.','local'=>true],
    ['company'=>'Webflow','role'=>'Senior Product Design Manager','date'=>'2026-04-17','status'=>'canceled','fit'=>'med','resume'=>'Richard_Lee_Resume_Webflow.docx','notes'=>'Role closed by Webflow.'],
    ['company'=>'Raptor Technologies','role'=>'Director of Product Design','date'=>'2026-04-17','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Raptor.docx','notes'=>'School safety. Two school-age kids. Personal mission.'],
    ['company'=>'Pinterest','role'=>'TPM, AI-Native Software Engineering','date'=>'2026-04-17','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Pinterest.docx','notes'=>'Claude Code portfolio reconstruction story.'],
    ['company'=>'Anthropic','role'=>'Engineering Manager, People Products','date'=>'2026-04-06','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Anthropic.docx','notes'=>'SF hybrid. AuDHD AI story.'],
    ['company'=>'Netflix','role'=>'TPM 5 — AI','date'=>'2026-03-10','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Updated.docx','notes'=>'OVERDUE for follow-up.'],
    ['company'=>'Adobe','role'=>'Delivery Consultant, Customer Journeys','date'=>'2026-03-23','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_Updated.docx','notes'=>'Role eliminated 6/27.'],
    ['company'=>'Zillow','role'=>'Senior Product Manager, Touring','date'=>'2026-03-23','status'=>'no','fit'=>'low','resume'=>'Richard_Lee_Resume_Updated.docx','notes'=>'Rejected.'],
    ['company'=>'Stryker','role'=>'Customer Experience Design Manager','date'=>'2026-04-27','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_Stryker.docx','notes'=>'Rejected 6/10. Medical device — Siemens direct match.'],
    ['company'=>'D&D Beyond (Wizards)','role'=>'Senior UX Designer (Contract)','date'=>'2026-04-24','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_DnD_Beyond.docx','notes'=>'Rejected 5/18 via Hasbro talent system.'],
    ['company'=>'Recidiviz','role'=>'Director of Product','date'=>'2026-04-22','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_Recidiviz.docx','notes'=>'Not selected — PM team leadership gap.'],
    ['company'=>'Kraken','role'=>'Senior Product Manager, Platform','date'=>'2026-04-22','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_Kraken.docx','notes'=>'Not selected. Formal PM gap.'],
    ['company'=>'Skylight','role'=>'Senior/Staff/Principal PM (ACF)','date'=>'2026-04-24','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Skylight.docx','notes'=>'Civic tech. ACF mission personal.'],
    ['company'=>'Salesforce','role'=>'Principal Design Program Manager','date'=>'2026-05-17','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Salesforce.docx','notes'=>'SF/Seattle hub preference. Ford DesignOps story maps directly.'],
    ['company'=>'Talkiatry','role'=>'Director of Product Design','date'=>'2026-05-17','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Talkiatry.docx','notes'=>'Mental health mission personal. Healthcare UX — Siemens/PerfectServe bridge. Player-coach.'],
    ['company'=>'Mindbody / Playlist','role'=>'Design Manager, Platform & Productivity','date'=>'2026-05-17','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Mindbody.docx','notes'=>'Fitness tech passion. AI Assistant design. Player-coach.'],
    ['company'=>'Autodesk','role'=>'Senior Manager, Experience Design (Forma)','date'=>'2026-05-17','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Autodesk.docx','notes'=>'AEC domain gap offset by construction experience.'],
    ['company'=>'CVS Health / Signify','role'=>'Senior Manager, UX Design — Scheduling & Execution','date'=>'2026-05-17','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_CVS_Signify.docx','notes'=>'Rejected 6/18. Claude Code called out by name in JD. Healthcare domain.'],
    ['company'=>'Anthropic','role'=>'Research Operations, Discovery','date'=>'2026-06-03','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Anthropic_ResearchOps.docx','notes'=>'Strongest Anthropic structural fit. Operational infrastructure for Discovery team.'],
    ['company'=>'Anthropic','role'=>'Customer Success Programs Manager','date'=>'2026-06-03','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Anthropic_CSPrograms.docx','notes'=>'AI-native programs builder angle. 1:many engagement design, change management.'],
    ['company'=>'Thyme Care','role'=>'AI Program & Governance Lead','date'=>'2026-06-03','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_ThymeCare.docx','notes'=>'Oncology care enablement. AI governance + healthcare + program management.'],
    ['company'=>'Stripe','role'=>'Design Program Manager, AI','date'=>'2026-06-03','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Stripe.docx','notes'=>'Strongest structural fit in entire search. AI-native design transformation. Remote.'],
    ['company'=>'FAR.AI','role'=>'Technical Project Manager','date'=>'2026-06-03','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_FAR_AI.docx','notes'=>'Rejected quickly. Recruiting gap was the risk.'],
    ['company'=>'iHerb','role'=>'Director of UX Design','date'=>'2026-06-03','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_iHerb.docx','notes'=>'Wellness/fitness personal connection. Transactional UX bridge. Remote.'],
    ['company'=>'Headway','role'=>'Design Director, Core Insurance & Design Systems','date'=>'2026-06-03','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Headway.docx','notes'=>'Mental health access mission personal. Design systems + foundational UX.'],
    ['company'=>'Jobgether (unknown)','role'=>'Director, AI Center of Excellence','date'=>'2026-06-03','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_AI_CoE_Director.docx','notes'=>'Employer identity unknown. Jobgether confidential partner.'],
    ['company'=>'GovAI','role'=>'Director of Operations (EOI)','date'=>'2026-06-06','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_CV_GovAI.docx','notes'=>'AI governance research org. Operations leadership. ET/GMT overlap from Knoxville.'],
    ['company'=>'AE Studio','role'=>'Alignment Research Manager','date'=>'2026-06-06','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_AEStudio.docx','notes'=>'ML consultancy funding alignment research. Fully remote.'],
    ['company'=>'SimplePractice','role'=>'Lead Program Manager','date'=>'2026-06-06','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_SimplePractice.docx','notes'=>'Rejected 6/30.'],
    ['company'=>'Consultants for Impact','role'=>'AI & Systems Lead/Director/VP','date'=>'2026-06-06','status'=>'active','fit'=>'med','resume'=>'','notes'=>'Nonprofit — AI workflow, CRM, automation. Deadline July 5, 2026.'],
    ['company'=>'Oracle Health','role'=>'Product Owner — Federal Arbitration Learning','date'=>'2026-06-07','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_Oracle.docx','notes'=>'IC3. Healthcare learning product, Agile PO, Section 508.'],
    ['company'=>'Oracle','role'=>'Product Manager (IC5)','date'=>'2026-06-07','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_Oracle_IC5.docx','notes'=>'Rejected. Generic IC5 shell.'],
    ['company'=>'Oracle','role'=>'Principal Consultant (IC3)','date'=>'2026-06-10','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_Oracle.docx','notes'=>'Rejected 6/18. Role 335216.'],
    ['company'=>'NVIDIA','role'=>'Developer Experience Manager','date'=>'2026-06-07','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_NVIDIA.docx','notes'=>'Dual-audience developer content standards. Claude Code differentiator.'],
    ['company'=>'General Motors','role'=>'Staff PM, AI Developer Productivity','date'=>'2026-06-07','status'=>'no','fit'=>'med','resume'=>'Richard_Lee_Resume_GM.docx','notes'=>'Rejected 6/10.'],
    ['company'=>'General Motors','role'=>'Staff PM, Core Experiences / In-Vehicle XP','date'=>'2026-06-07','status'=>'no','fit'=>'high','resume'=>'Richard_Lee_Resume_GM_IVX.docx','notes'=>'Rejected 6/10. Strongest GM fit.'],
    ['company'=>'Momentive Software','role'=>'Senior Manager, User Experience','date'=>'2026-06-10','status'=>'active','fit'=>'high','resume'=>'Richard_Lee_Resume_Momentive.docx','notes'=>'Cross-product platform UX, AI interface design, design systems.'],
    ['company'=>'Encode AI','role'=>'Senior Operations Manager / Head of Operations','date'=>'2026-06-12','status'=>'active','fit'=>'med','resume'=>'Richard_Lee_Resume_EncodeAI.docx','notes'=>'AI policy advocacy nonprofit. Mission resonant.'],
    ['company'=>'Atlassian','role'=>'Senior Design Manager, Rovo AI','date'=>'2026-06-12','status'=>'active','fit'=>'high','resume'=>'Atlassian - Senior Design Manager Rovo.docx','notes'=>'Flagship AI experience. Design systems, AI interface design.'],
    ['company'=>'Lumen','role'=>'AI Product Strategy & Experience Director','date'=>'','status'=>'hold','fit'=>'high','resume'=>'','notes'=>'Reports to Chief AI Officer. Queued for materials build.'],
    ['company'=>'H&R Block','role'=>'Director of Product Design, Financial Products & Native Apps','date'=>'','status'=>'hold','fit'=>'high','resume'=>'','notes'=>'Mobile apps, digital banking, auth/identity UX, design systems, AI workflows. Queued for materials build.'],
    ['company'=>'Unity Technologies','role'=>'UX Design Leader, GTM & Marketplace','date'=>'2026-06-17','status'=>'active','fit'=>'med','resume'=>'Unity__UX_Design_Leader.docx','notes'=>'Lifelong gamer and world-builder. https://unity.com/careers/positions/7926724'],
    ['company'=>'GovCIO','role'=>'Principal UX Program Manager','date'=>'2026-06-17','status'=>'active','fit'=>'high','resume'=>'GovCIO__Principal_UX_Program_Manager.docx','notes'=>'VA digital product portfolio. Practice-building, governance, healthcare UX anchor.'],
    ['company'=>'Apply Digital','role'=>'VP / Director of Experience (ACx)','date'=>'2026-06-17','status'=>'active','fit'=>'med','resume'=>'Apply_Digital__VP_Experience.docx','notes'=>'Agentic CX consultancy. Agency gap named. AI-native differentiator.'],
    ['company'=>'Samsara','role'=>'Senior PM, In-Vehicle Experience','date'=>'2026-06-17','status'=>'no','fit'=>'med','resume'=>'Samsara__Senior_PM_IVX.docx','notes'=>'Rejected 6/25. In-cab AI companion. Ford fleet domain match. PM title gap named.'],
    ['company'=>'Twilio','role'=>'Staff Product Manager, Enterprise AI','date'=>'2026-06-17','status'=>'active','fit'=>'med','resume'=>'Twilio__Staff_PM_Enterprise_AI.docx','notes'=>'Enterprise AI agentic workflows. PM gap named. No cover letter field.'],
    ['company'=>'Engine','role'=>'Sr. Product Design Manager, Travel','date'=>'2026-06-17','status'=>'active','fit'=>'med','resume'=>'Engine__Sr_Product_Design_Manager.docx','notes'=>'B2B travel platform. Player-coach, AI-native.'],
    ['company'=>'Mercury','role'=>'Senior Design Operations Program Manager','date'=>'2026-06-17','status'=>'no','fit'=>'high','resume'=>'Mercury__Senior_DesignOps_Program_Manager.docx','notes'=>'Rejected 6/26. Fintech banking design ops. DesignOps core fit.'],
    ['company'=>'Figma','role'=>'Design Manager','date'=>'2026-06-17','status'=>'active','fit'=>'high','resume'=>'Figma__Design_Manager.docx','notes'=>'Highest-fit role in search. Enterprise Figma admin, Config 2024, AI-native, published AI adoption writing.'],
    ['company'=>'Stitch Fix','role'=>'Manager, Product Design','date'=>'2026-06-17','status'=>'active','fit'=>'med','resume'=>'StitchFix__Manager_Product_Design.docx','notes'=>'Consumer domain gap named. Multi-persona interdependent journey framing.'],
    ['company'=>'Johnson & Johnson MedTech','role'=>'Design Leader, Polyphonic AI Lab','date'=>'','status'=>'active','fit'=>'high','resume'=>'JnJ__Design_Leader_Polyphonic_AI_Lab.docx','notes'=>'Regulated healthcare AI platform. Siemens direct domain match. 25% travel. Materials built, not yet submitted.'],
    ['company'=>'Thrivent','role'=>'Manager, Design Systems & Design Operations','date'=>'','status'=>'hold','fit'=>'high','resume'=>'Thrivent__Manager_Design_Systems_DesignOps.docx','notes'=>'Design systems + DesignOps combined. Cloudflare blocking portal.'],
    ['company'=>'BigPanda','role'=>'Director, UX Design','date'=>'2026-06-18','status'=>'active','fit'=>'high','resume'=>'BigPanda__Director_UX_Design.docx','notes'=>'First design hire, AIOps to Agentic ITOps. Quest Software ITOps domain bridge. Bay Area/NY preference noted.'],
    ['company'=>'MetaMask / Consensys','role'=>'Design Director, Transactions','date'=>'2026-06-18','status'=>'active','fit'=>'high','resume'=>'MetaMask__Design_Director_Transactions.docx','notes'=>'10+ years crypto/Web3 background as differentiator. Transaction trust UX.'],
    ['company'=>'Intertek People Assurance','role'=>'Head of Design','date'=>'2026-06-19','status'=>'active','fit'=>'high','resume'=>'Intertek__Head_of_Design.docx','notes'=>'Transformation mandate. Legacy-to-modern UI centerpiece — Siemens PET scanner modernization story. HR tech domain via Predictive Index.'],
    ['company'=>'HighLevel','role'=>'Senior Director, Product Design','date'=>'2026-06-19','status'=>'active','fit'=>'high','resume'=>'HighLevel__Senior_Director_Product_Design.docx','notes'=>'Highest scope design leadership role in search. Agency/SMB SaaS platform. Design systems transformation + AI-native UX.'],
    ['company'=>'BILL','role'=>'Senior Staff Design Program Manager','date'=>'2026-06-26','status'=>'no','fit'=>'high','resume'=>'BILL__Resume__Senior_Staff_Design_Program_Manager.docx','notes'=>'Rejected 6/29 — role filled. Architect of Design Team Operating System. 60+ designers. UX quality programs, ritual architecture, AI integration. Zone 3 comp range.'],
    ['company'=>'Loop Returns','role'=>'Director of Design','date'=>'2026-06-26','status'=>'active','fit'=>'high','resume'=>'Loop__Resume__Director_of_Design.docx','notes'=>'Agentic commerce ops platform. Interface-to-autonomous-system transition. Design systems for non-designers, brand+product+research span. ET hub preferred. Loop earplug customer — personal product connection.'],
    ['company'=>'AcuityMD','role'=>'Senior Product Manager','date'=>'2026-06-26','status'=>'active','fit'=>'med','resume'=>'AcuityMD__Resume__Senior_Product_Manager.docx','notes'=>'PM title gap named and reframed. MedTech data platform — Siemens clinical domain anchor. Production LLM delivery differentiator. Stretch application.'],
    ['company'=>'Jeppesen ForeFlight','role'=>'Director of Product Design','date'=>'2026-06-29','status'=>'active','fit'=>'med','resume'=>'Jeppesen_ForeFlight__Resume__Director_of_Product_Design.docx','notes'=>'Aviation software. FAA Part 107 + active ForeFlight use as personal hook.'],
    ['company'=>'InStride Health','role'=>'Director of Design','date'=>'2026-06-26','status'=>'active','fit'=>'high','resume'=>'InStride_Health__Resume__Director_of_Design.docx','notes'=>'Healthcare mental health domain. Submitted in prior session.'],
    ['company'=>'Kong','role'=>'Staff Design Technologist','date'=>'2026-06-26','status'=>'active','fit'=>'med','resume'=>'Kong__Resume__Staff_Design_Technologist.docx','notes'=>'Developer tooling. Cover letter cut ~50%. Submitted in prior session.'],
    ['company'=>'CVS Health','role'=>'Senior Experience Designer','date'=>'2026-06-27','status'=>'active','fit'=>'med','resume'=>'CVS_Health__Resume__Senior_Experience_Designer.docx','notes'=>'Built and submitted following session deferral.'],
    ['company'=>'ServiceTitan','role'=>'Group Manager, Product Design','date'=>'2026-06-26','status'=>'active','fit'=>'med','resume'=>'ServiceTitan__Resume__Group_Manager_Product_Design.docx','notes'=>'Submitted.'],
    ['company'=>'GM','role'=>'Senior Brand Product Designer, Emerging Experiences','date'=>'2026-06-26','status'=>'active','fit'=>'med','resume'=>'GM__Resume__Senior_Brand_Product_Designer_Emerging_Experiences.docx','notes'=>'Submitted.'],
    ['company'=>'Epoch AI','role'=>'Senior Product Designer','date'=>'2026-06-16','status'=>'interview','fit'=>'high','resume'=>'EpochAI__Senior_Product_Designer.docx','notes'=>'Active interview process. Recruiter screen + designer interview complete. Next: data viz exercise, paid take-home, interview with Head of Operations María de la Lama (LSE/Mercatus, EA-aligned). Strategy: telegraph leadership capability without overselling.'],
    ['company'=>'Y12 Federal Credit Union','role'=>'Director of Digital Services','date'=>'2026-06-10','status'=>'active','fit'=>'high','resume'=>'Y12_FCU__Director_Digital_Services.docx','notes'=>'Warm referral via Lance Whitworth, checking with CTIO Todd. Outcome pending.','local'=>true],
    ['company'=>'Higharc','role'=>'Director, UX Product Design','date'=>'2026-06-29','status'=>'active','fit'=>'high','resume'=>'Higharc__Director_UX_Product_Design.docx','notes'=>'Construction tech, generative parametric design. SketchUp/FreeCAD/Blender personal projects + drone/trim carpentry construction proximity story.'],
    ['company'=>'Routeware','role'=>'Director of UX','date'=>'2026-06-29','status'=>'active','fit'=>'high','resume'=>'Routeware__Director_of_UX.docx','notes'=>'Waste/recycling SaaS. Post-acquisition portfolio unification across 5 product lines. PerfectServe + Siemens function-building anchor. WCAG studied to cert depth (not certified).'],
    ['company'=>'Maven Clinic','role'=>'Senior Staff Product Designer','date'=>'2026-06-29','status'=>'active','fit'=>'high','resume'=>'Maven__Senior_Staff_Product_Designer.docx','notes'=>"Women's health AI-native. Remote eligible — confirmed. IC reframe applied. Siemens clinical AI trust anchor. Scripps + Minotaur production code history. \$195K-\$230K base."],
    ['company'=>'Aurora Solar','role'=>'Head of Design','date'=>'2026-06-29','status'=>'active','fit'=>'high','resume'=>'Aurora_Solar__Head_of_Design.docx','notes'=>'Clean energy SaaS. 5-person team to grow. Siemens multi-product + Ford DesignOps anchor. Tier 3 comp $190K-$242K.'],
    ['company'=>'Stack Overflow','role'=>'Director of Product Design & Research','date'=>'2026-06-29','status'=>'active','fit'=>'high','resume'=>'Stack_Overflow__Director_Product_Design_Research.docx','notes'=>'AI-native pivot, reports to CPTO. Design lives in chat/API, not UI — direct map to Ford agentic workflow delivery. $190K-$250K.'],
    ['company'=>'Citrix / Cloud Software Group','role'=>'Principal Product Designer','date'=>'2026-06-29','status'=>'active','fit'=>'med','resume'=>'Citrix__Principal_Product_Designer.docx','notes'=>'IC role, enterprise admin/end-user UX. Siemens + Quest PAM anchor. Comp varies by location tier $177K-$318K.'],
];

$pdo = db();
$reset = !empty($_GET['reset']);

if ($reset) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('TRUNCATE TABLE applications');
    $pdo->exec('TRUNCATE TABLE organizations');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

$inserted_orgs = 0;
$inserted_apps = 0;
$org_cache = [];

$org_stmt = $pdo->prepare('INSERT IGNORE INTO organizations (name) VALUES (?)');
$get_org  = $pdo->prepare('SELECT id FROM organizations WHERE name = ?');
$app_stmt = $pdo->prepare(
    'INSERT INTO applications (org_id, role_title, date_applied, status, fit, resume_file, notes, is_local)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

foreach ($seed as $row) {
    $company = $row['company'];

    if (!isset($org_cache[$company])) {
        $org_stmt->execute([$company]);
        if ($org_stmt->rowCount()) $inserted_orgs++;
        $get_org->execute([$company]);
        $org_cache[$company] = (int)$get_org->fetchColumn();
    }

    $date  = $row['date'] ?: null;
    $local = !empty($row['local']) ? 1 : 0;

    $app_stmt->execute([
        $org_cache[$company],
        $row['role'],
        $date,
        $row['status'],
        $row['fit'],
        $row['resume'],
        $row['notes'],
        $local,
    ]);
    $inserted_apps++;
}

header('Content-Type: application/json');
echo json_encode([
    'seed'   => 'done',
    'reset'  => $reset,
    'orgs'   => $inserted_orgs,
    'apps'   => $inserted_apps,
], JSON_PRETTY_PRINT);
