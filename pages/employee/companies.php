<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 COMPANIES

            ================================================== -->

            <section class="page" id="page-companies">

 

                <div class="page-heading">

                    <div>

                        <div class="eyebrow" style="color:#1688ba">Placement & Advisory</div>

                        <h1>Companies &amp; Assigned Interns</h1>

                        <p>View partner companies, assigned students, total logged hours, absence records, and weekly journals.</p>

                    </div>

 

                    <button class="btn btn-primary" id="addCompanyPageBtn">+ Add Company</button>

                </div>

 

                <?php if ($companiesFlash): ?>

                    <div class="interns-flash <?= ($companiesFlash["type"] ?? "") === "success" ? "success" : "error" ?>" role="<?= ($companiesFlash["type"] ?? "") === "error" ? "alert" : "status" ?>">

                        <?= htmlspecialchars($companiesFlash["message"] ?? "", ENT_QUOTES, "UTF-8") ?>

                    </div>

                <?php endif; ?>

 

                <div class="section-card">

                    <div class="section-head">

                        <div>

                            <div class="section-kicker">Partner Organizations</div>

                            <h2 class="section-title">Companies &amp; Student Placement</h2>

                            <p class="section-sub">Assign student interns to companies and monitor their progress, absences, and journals.</p>

                        </div>

 

                        <div class="toolbar">

                            <input class="search" id="companyPageSearch" placeholder="Search company or student...">

                        </div>

                    </div>

 

                    <div id="companyPlacementContainer">

                        <!-- Populated by JS -->

                    </div>

                </div>

 

            </section>

 

            