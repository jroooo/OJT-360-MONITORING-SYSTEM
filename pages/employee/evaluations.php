<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 EVALUATIONS

            ================================================== -->

            <section class="page" id="page-evaluations">

 

                <div class="page-heading">

                    <div>

                        <div class="eyebrow" style="color:#1688ba">Performance</div>

                        <h1>Evaluations</h1>

                        <p>Review and record performance evaluations for registered students.</p>

                    </div>

 

                    <button class="btn btn-primary" id="startEvaluationBtn">Start evaluation</button>

                </div>

 

                <div class="section-card">

                    <?php if ($evaluationFlash && ($evaluationFlash["type"] ?? "") !== "success"): ?>

                        <div class="interns-flash error" role="alert">

                            <?= htmlspecialchars($evaluationFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>

                        </div>

                    <?php endif; ?>

 

                    <?php if ($evaluationPageError !== ""): ?>

                        <div class="interns-flash error" role="alert">

                            <?= htmlspecialchars($evaluationPageError, ENT_QUOTES, "UTF-8") ?>

                        </div>

                    <?php endif; ?>

 

                    <?php if ($studentProgressPageError !== ""): ?>

                        <div class="interns-flash error" role="alert">

                            <?= htmlspecialchars($studentProgressPageError, ENT_QUOTES, "UTF-8") ?>

                        </div>

                    <?php endif; ?>

 

                    <div class="section-head">

                        <div>

                            <div class="section-kicker">Student Roster</div>

                            <h2 class="section-title">Registered students</h2>

                            <p class="section-sub">The latest saved evaluation and feedback are shown for each registered student.</p>

                        </div>

 

                        <div class="action-row">

                            <input class="search" id="evalSearch" style="width:180px" placeholder="Search intern...">

                            <button class="small-btn">Filter</button>

                            <button class="small-btn">Export</button>

                        </div>

                    </div>

 

                    <div class="evaluation-roster" id="evaluationRoster"></div>

 

                    <div class="feedback">

                        <div>

                            <h3>Structured feedback that supports growth</h3>

                            <p>Rate each performance criterion from 1 to 5, record feedback, then choose whether to save the evaluation as a draft or complete the review.</p>

                        </div>

                    </div>

                </div>

            </section>

 

            
