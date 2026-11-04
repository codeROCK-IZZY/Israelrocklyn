<?php
/**
 * Everything said about the person. Only facts the owner has stated: no invented employers, dates or
 * credentials. Edit here; every page reads from this file.
 */

declare(strict_types=1);

return [
    'name' => 'Israel Rocklyn',
    'headline' => 'AI, ML & Cloud Engineer',
    'location' => 'United States',
    'github' => 'https://github.com/codeROCK-IZZY',
    'intro' => 'I build AI systems that prove they work, and the cloud platforms they run on.',
    'summary' => 'AI, ML & Cloud Engineer and Full-Stack Developer. I take products from data and models through the '
        . 'API and interface to the infrastructure, and every one ships with tests, CI and measured results. '
        . 'I also build WordPress sites and run digital marketing, so I can help a product reach people, not only work.',
    'roles' => [
        'AI Engineer', 'Machine Learning Engineer', 'LLM / GenAI Engineer', 'MLOps Engineer',
        'AI Solutions Engineer', 'Cloud & Platform Engineer', 'Full-Stack Developer', 'WordPress Developer',
    ],
    'education' => [
        ['degree' => 'M.S. Computer Science', 'focus' => 'Concentration in Artificial Intelligence',
         'note' => 'Graduate-level focus on artificial intelligence and machine learning: the foundation behind the AI systems in this portfolio.'],
        ['degree' => 'B.Sc. Information Systems', 'focus' => 'Business technology and data',
         'note' => 'How technology serves a business: the reason my projects start from a real problem and end with documentation for the people who run them.'],
    ],
    'principles' => [
        ['title' => 'Measure it', 'text' => 'AI features ship with an evaluation, not a demo. Every project here reports numbers from a repeatable test, including the ones that came out badly.'],
        ['title' => 'Say "I don\'t know"', 'text' => 'Answers cite their source or decline. A system that admits what it can\'t answer is one people can trust with the rest.'],
        ['title' => 'Secure by default', 'text' => 'Least privilege, no long-lived keys, input validation and audit trails, from the first commit rather than before launch.'],
        ['title' => 'Write it down', 'text' => 'Design docs, runbooks and known limitations in every repository, so the next engineer can operate and change it.'],
    ],
    // Capability => the projects that demonstrate it (slugs from projects.php).
    'capabilities' => [
        'Retrieval-augmented generation with citations' => ['multimodal-ai-assistant', 'ai-research-engine', 'intelligent-document-ai', 'ai-developer-intelligence'],
        'LLM agents and tools with human approval' => ['autonomous-business-platform', 'multimodal-ai-assistant'],
        'Prompt-injection and abuse resistance' => ['multimodal-ai-assistant', 'autonomous-business-platform'],
        'Classical ML with explanations (XGBoost, SHAP)' => ['fraud-detection-engine', 'ai-predictive-maintenance', 'aws-mlops-platform'],
        'Honest evaluation and measured results' => ['fraud-detection-engine', 'aws-mlops-platform', 'multimodal-ai-assistant', 'distributed-ai-inference', 'aws-serverless-event-platform', 'aws-security-guardrails'],
        'MLOps: registry, promotion gates, drift monitoring' => ['aws-mlops-platform', 'ai-predictive-maintenance'],
        'Model serving: batching, load shedding, failover' => ['distributed-ai-inference'],
        'Speech, vision and OCR pipelines' => ['multimodal-ai-assistant', 'intelligent-document-ai'],
        'Real-time streaming (Kafka)' => ['fraud-detection-engine'],
        'AWS foundations as code (Terraform)' => ['enterprise-aws-cloud-platform', 'aws-mlops-platform', 'aws-serverless-event-platform'],
        'Event-driven serverless: sagas, outbox, exactly-once effects' => ['aws-serverless-event-platform'],
        'Cloud security: posture, IAM analysis, safe auto-remediation' => ['aws-security-guardrails', 'enterprise-aws-cloud-platform'],
        'Kubernetes and autoscaling' => ['distributed-ai-inference'],
        'Code intelligence (parsing, call graphs)' => ['ai-developer-intelligence'],
    ],
    'skills' => [
        'AI & machine learning' => ['RAG with citations', 'LLM agents with human approval', 'Claude API', 'LangGraph', 'scikit-learn', 'XGBoost', 'SHAP', 'ONNX Runtime', 'Whisper', 'OCR', 'pgvector', 'MLflow', 'Model evaluation'],
        'Backend & data' => ['Python', 'Go', 'FastAPI', 'PostgreSQL', 'SQLAlchemy', 'Apache Kafka', 'Job queues', 'REST API design', 'PHP'],
        'Cloud & platform' => ['AWS', 'Terraform', 'Lambda', 'Step Functions', 'DynamoDB', 'EventBridge / SQS', 'Security Hub', 'SageMaker', 'ECS / RDS', 'Kubernetes', 'KEDA', 'Docker', 'GitHub Actions', 'Prometheus', 'Grafana', 'Checkov'],
        'Frontend' => ['TypeScript', 'React', 'Tailwind CSS', 'Vite', 'Accessible UI'],
    ],
    'services' => [
        ['title' => 'WordPress websites', 'text' => 'Business sites built with Elementor, Gutenberg, Divi or WPBakery, with ACF and PHP for custom features.'],
        ['title' => 'SEO & performance', 'text' => 'Technical audits, keyword research and Core Web Vitals fixes, using Ahrefs, SEMrush, Yoast, Rank Math and PageSpeed Insights.'],
        ['title' => 'Analytics & conversion', 'text' => 'GA4, Tag Manager and Looker Studio dashboards; Hotjar or Clarity heatmaps and A/B tests to improve conversion.'],
        ['title' => 'Campaigns & CRM', 'text' => 'Google Ads, Meta Business Suite, HubSpot, Mailchimp and GoHighLevel: campaigns connected to the CRM and measured end to end.'],
        ['title' => 'E-commerce', 'text' => 'WooCommerce and Shopify stores with Stripe, PayPal or Square payments.'],
        ['title' => 'Hosting & accessibility', 'text' => 'cPanel hosting, DNS and SSL, CDN and caching, and accessibility to WCAG 2.1 / 2.2.'],
    ],
];
