<?php /* Employee dashboard page fragment; included by dashboard(employee).php. */ ?>
<!-- =================================================

                 MESSAGES

            ================================================== -->

            <section class="page" id="page-messages">

 

                <div class="page-heading">

                    <div>

                        <div class="eyebrow" style="color:#1688ba">Communication</div>

                        <h1>Company coordination</h1>

                        <p>Documented conversations between company partners and OJT employees.</p>

                    </div>

 

                    <button class="btn btn-primary" id="newMessageBtn">+ New message</button>

                </div>

 

                <div class="section-card message-layout">

                    <div class="conversation-list">

                        <div class="conversation-search">

                            <input class="search" id="conversationSearch" style="width:100%" placeholder="Search companies...">

                        </div>

 

                        <div id="companyConversationList" aria-live="polite"></div>

                    </div>

 

                    <div class="chat">

                        <div class="chat-head">

                            <div>

                                <div class="chat-name" id="chatName">Select a company</div>

                                <div class="chat-status" id="chatStatus">Company coordination</div>

                            </div>

                            <button class="small-btn">⋮</button>

                        </div>

 

                        <div class="chat-body" id="chatBody">

                            <div data-empty-chat="true" style="padding:24px;text-align:center;color:#8597a6;font-size:12px;">Choose a company to view its conversation.</div>

                        </div>

 

                        <div class="chat-input">

                            <button class="small-btn">＋</button>

                            <input id="messageInput" placeholder="Write a message..." disabled>

                            <button class="btn btn-primary" id="sendMessageBtn" type="button" disabled>Send</button>

                        </div>

                    </div>

                </div>

            </section>

 

            