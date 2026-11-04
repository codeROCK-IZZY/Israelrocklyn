<?php
/**
 * Engineering notes: short write-ups of real problems met while building the projects. Every number
 * comes from the linked repository. Blocks: ['p', text] paragraph, ['h', text] subheading,
 * ['list', [items]] bullet list, ['code', text] preformatted.
 */

declare(strict_types=1);

return [
    'littles-law-model-serving' => [
        'title' => "Little's law is the most important number in model serving",
        'date' => '2026-11-03',
        'project' => 'distributed-ai-inference',
        'summary' => 'With a generous queue, an inference service under 2× load served 0.05 requests a second. One formula fixed it: 38.6.',
        'body' => [
            ['p', 'Inference servers usually protect themselves with a queue: requests wait their turn, and the queue is made long so nothing is "dropped". I built it that way first, with each worker accepting 64 inputs in flight, and then offered it twice the traffic it could handle.'],
            ['p', 'It served 0.05 requests per second. 99.9% of requests timed out. The server was 100% busy the whole time, computing answers that arrived after their 2-second deadline.'],
            ['h', 'Why a long queue collapses'],
            ['p', "A worker that handles about 45 texts per second, with 64 texts queued, has about 1.4 seconds of work in front of every new request. Add the request's own time and some variance, and almost everything misses a 2-second deadline. Every timed-out request still consumed compute, so the useful throughput fell to almost nothing."],
            ['h', 'The fix'],
            ['p', "Little's law says that work in flight equals throughput × time in the system. If a worker serves about 45 texts per second and the latency budget is about 0.35 seconds, it should hold about 16 texts in flight. The gateway now admits work only up to that limit and answers everything above it immediately with 503 and Retry-After."],
            ['list', ['Without admission control: 0.05 requests/s served, 99.9% timed out.', 'With it: 38.6 requests/s served, p95 567 ms, no timeouts.', 'On the accelerator-like backend: 173 requests/s with it, 4.7 without.']],
            ['p', 'Refusing quickly is a feature. The client gets a clear signal in about a millisecond and can retry or back off, instead of waiting two seconds for a timeout.'],
        ],
    ],
    'drift-monitor-false-alarms' => [
        'title' => 'Why my drift monitor cried wolf on half the stable months',
        'date' => '2026-11-03',
        'project' => 'aws-mlops-platform',
        'summary' => 'A 0.02 tolerance on monthly PR-AUC raised 6 alarms in 12 months where nothing had changed. The noise was in the measurement, not the model.',
        'body' => [
            ['p', 'The MLOps platform raises an alert when a production model gets worse, and the alert starts a retraining run. The first version reused the tolerance from the promotion gates: an alert when PR-AUC drops more than 0.02 below the model\'s baseline.'],
            ['p', 'To test it, I ran the monitoring decision on 12 extra months of stable synthetic data, months where nothing had changed. It raised an alarm on 6 of them.'],
            ['h', 'Where the noise comes from'],
            ['p', 'A month has about 200 equipment failures. With that few positives, monthly PR-AUC naturally wobbles by about ±0.03. A 0.02 tolerance sits inside the noise, so half the stable months look like a degradation.'],
            ['p', 'The promotion gate is different: it scores the candidate and the champion on the same month, so the month\'s noise affects both equally and cancels out. A 0.02 tolerance is right there.'],
            ['h', 'The fix, and the finding behind it'],
            ['list', ['Monitoring compares across months, so it now uses 0.08 (about 2.5 standard deviations): 0 false alarms in 12 stable months.', 'Promotion compares models on the same data and keeps 0.02.', 'Every simulated change was still detected in the month it started.']],
            ['p', 'A second finding: a new failure mode was invisible to input-drift checks. Inputs had already drifted for months, and the prediction distribution barely moved. What revealed it was measured performance, with PR-AUC falling from 0.77 to 0.39. Monitoring without labels would have missed it.'],
        ],
    ],
    'honest-evaluation-fraud' => [
        'title' => 'A random split said 0.929. The honest number was 0.709.',
        'date' => '2026-11-01',
        'project' => 'fraud-detection-engine',
        'summary' => 'How you split the data changes a fraud model\'s score more than how you build the model.',
        'body' => [
            ['p', 'Fraud models are usually evaluated by shuffling transactions and holding out a random 20%. For the fraud engine, that would have reported a PR-AUC of 0.929.'],
            ['p', 'In production, a model is trained on the past and scores the future. Fraud patterns move, cards and devices recur, and a random split lets the model peek at the same card\'s other transactions. Evaluated the way it will be used, trained on earlier weeks and tested on later ones, the same model scores 0.709.'],
            ['h', 'What I report instead'],
            ['list', ['Time-based splits only.', 'Results at the operating point the analysts actually use: about 31 alerts a day, catching 93% of fraud dollars, with 96% precision in the critical band.', 'The random-split number, shown next to the honest one, so nobody mistakes it for performance.']],
            ['p', 'A lower number that holds up beats a higher number that collapses on the first real month. It is also the number an employer can trust.'],
        ],
    ],
    'onnx-thread-spinning' => [
        'title' => 'How idle threads burned two thirds of my inference capacity',
        'date' => '2026-11-03',
        'project' => 'distributed-ai-inference',
        'summary' => 'Model workers ran at a third of their measured speed inside containers. The CPU was busy waiting, not computing.',
        'body' => [
            ['p', 'Called directly, the embedding model handled about 55 texts per second in its container. Inside the HTTP worker, under load, it managed 17. Same model, same container, same 2-CPU limit.'],
            ['h', 'Following the CPU'],
            ['p', 'The container\'s cgroup statistics showed it was throttled in about 3 of every 4 scheduling periods. Per-thread CPU time showed two ONNX Runtime threads doing almost all the work, about three times more CPU per text than in the direct test.'],
            ['p', 'ONNX Runtime\'s thread pool spin-waits after each call, ready for the next one. Under a container CPU quota that spinning is billed like real work, so the worker spent its quota waiting and was then throttled when real work arrived.'],
            ['h', 'Two fixes'],
            ['list', ['Turn spinning off (session.intra_op.allow_spinning = 0). The library used to load the model didn\'t expose the option, so the worker sets it while the model is constructed.', 'Encode responses with orjson straight from the numpy array. The framework\'s generic JSON encoder was costing about 13 ms of CPU per request on the event-loop thread.']],
            ['p', 'The lesson is to profile inside the real environment. Neither problem shows up in a notebook.'],
        ],
    ],
    'when-to-say-i-dont-know' => [
        'title' => "Cosine similarity can't tell you when to say \"I don't know\"",
        'date' => '2026-11-02',
        'project' => 'multimodal-ai-assistant',
        'summary' => 'Deciding whether files answer a question needs a different model from finding the closest passage.',
        'body' => [
            ['p', 'A grounded assistant has to refuse when the user\'s files don\'t contain the answer. The obvious approach is a threshold on retrieval similarity: if the best passage isn\'t similar enough, decline.'],
            ['p', 'I measured it on labelled answerable and unanswerable questions. To keep 92% of answerable questions, the cosine threshold could only reject 57% of the unanswerable ones. Embeddings put "the meal limit on a business trip" close to "the travel policy" whether or not the policy states a meal limit.'],
            ['h', 'What works'],
            ['p', 'A cross-encoder reads the question and the candidate passage together, and scores whether the passage actually answers it. At a threshold chosen from a measured sweep, it keeps 92% of answerable questions and correctly declines 86% of unanswerable ones.'],
            ['list', ['Retrieval (embeddings + lexical overlap) finds candidates quickly.', 'The cross-encoder decides answerability and picks the passage to quote.', 'Every answer cites the file and location, or says it couldn\'t find it.']],
        ],
    ],
];
