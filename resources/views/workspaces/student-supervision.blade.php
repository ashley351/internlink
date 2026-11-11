@extends('layouts.internlink')
@section('title', 'University support')
@section('content')
<div class="wrap dash">
    <div class="dash-head">
        <span class="eyebrow">University support</span>
        <h1>Student letter office and placement support</h1>
        <p class="muted">Request your introductory letter, submit placement documents, track supervisor feedback and post your weekly logbooks.</p>
    </div>

    @if(session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    <div class="stats">
        <div class="stat"><strong>2</strong><span>Letters to manage</span></div>
        <div class="stat"><strong>{{ $assignment ? '1' : '0' }}</strong><span>Assigned supervisor</span></div>
        <div class="stat"><strong>{{ $visits->count() }}</strong><span>Visit requests</span></div>
        <div class="stat"><strong>{{ $messages->count() }}</strong><span>Messages</span></div>
    </div>

    <div class="dash-grid">
        <section class="grid" style="align-content:start; gap:18px">
            <article class="card">
                <div class="row">
                    <div>
                        <span class="eyebrow">Introductory letter</span>
                        <h2 style="margin:7px 0 0">Request an introductory letter</h2>
                    </div>
                    <span class="tag">Ready</span>
                </div>
                <p class="muted" style="margin:12px 0 18px">Ask the university office to support your internship application with an official letter.</p>
                <form method="POST" action="#">
                    @csrf
                    <div class="field">
                        <label for="letter-purpose">Purpose</label>
                        <select class="input" id="letter-purpose" name="letter_purpose">
                            <option>Internship placement</option>
                            <option>Industrial attachment</option>
                            <option>Field attachment</option>
                            <option>Research placement</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="organisation-name">Organisation / host</label>
                        <input class="input" id="organisation-name" name="organisation_name" value="BrightLabs Uganda" maxlength="180">
                    </div>
                    <div class="field">
                        <label for="letter-notes">Additional details</label>
                        <textarea class="input" id="letter-notes" name="letter_notes" rows="3" placeholder="Mention the placement dates, host details and any urgency."></textarea>
                    </div>
                    <button class="button" type="submit">Request letter</button>
                </form>
            </article>

            <article class="card">
                <div class="row">
                    <div>
                        <span class="eyebrow">Placement letter</span>
                        <h2 style="margin:7px 0 0">Submit your placement letter</h2>
                    </div>
                    <span class="tag warn">Pending</span>
                </div>
                <p class="muted" style="margin:12px 0 18px">Upload the official appointment or placement letter from the host organisation.</p>
                <form method="POST" action="#" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label for="placement-file">Upload file</label>
                        <input class="input" id="placement-file" type="file" name="placement_letter" accept=".pdf,.doc,.docx,image/*">
                    </div>
                    <div class="field">
                        <label for="placement-comments">Notes for the placement office</label>
                        <textarea class="input" id="placement-comments" name="placement_comments" rows="3" placeholder="Add the start date, host contact, or any documents attached."></textarea>
                    </div>
                    <button class="button" type="submit">Hand in placement letter</button>
                </form>
            </article>

            <article class="card">
                <div class="row">
                    <div>
                        <span class="eyebrow">Weekly logbook</span>
                        <h2 style="margin:7px 0 0">Post a new logbook entry</h2>
                    </div>
                    <span class="tag">{{ $assignment ? 'Supervisor linked' : 'Awaiting supervisor' }}</span>
                </div>
                <form method="POST" action="{{ route('student.logbooks.store') }}">
                    @csrf
                    <div class="field">
                        <label for="week_number">Week number</label>
                        <input class="input" id="week_number" type="number" name="week_number" min="1" max="52" value="{{ $nextWeek ?? 1 }}" required>
                    </div>
                    <div class="field">
                        <label for="work_summary">What work did you do?</label>
                        <textarea class="input" id="work_summary" name="work_summary" rows="4" required maxlength="5000" placeholder="Describe the tasks and responsibilities handled this week."></textarea>
                    </div>
                    <div class="field">
                        <label for="learnings">What did you learn?</label>
                        <textarea class="input" id="learnings" name="learnings" rows="4" required maxlength="5000" placeholder="Summarise your learning, challenges and key takeaways."></textarea>
                    </div>
                    <div class="field">
                        <label for="hours_worked">Hours worked</label>
                        <input class="input" id="hours_worked" type="number" name="hours_worked" min="1" max="100" value="40" required>
                    </div>
                    <button class="button" type="submit">Submit logbook</button>
                </form>
            </article>
        </section>

        <aside class="grid" style="align-content:start; gap:18px">
            <section class="card">
                <span class="eyebrow">Supervisor</span>
                @if($assignment)
                    <h2 style="margin:7px 0 6px">{{ $assignment->supervisor->name }}</h2>
                    <p class="muted">Assigned university supervisor · {{ $user->university }}</p>
                    <div class="chat" style="margin:20px 0">
                        @forelse($messages as $message)
                            <div class="bubble {{ $message->sender_id === $user->id ? 'mine' : '' }}">
                                <small>{{ $message->sender->name }} · {{ $message->created_at->format('j M, g:i a') }}</small><br>
                                {{ $message->body }}
                            </div>
                        @empty
                            <p class="muted">Start the conversation with your supervisor.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('student.messages.store') }}">
                        @csrf
                        <div class="field">
                            <label for="body">Send a message</label>
                            <textarea class="input" id="body" name="body" rows="3" required maxlength="3000" placeholder="Share an update, ask for guidance or confirm details."></textarea>
                        </div>
                        <button class="button">Send message</button>
                    </form>
                @else
                    <div class="notice">You do not have a supervisor assigned yet. Contact the placement office at {{ $user->university }}.</div>
                @endif
            </section>

            <section class="card">
                <span class="eyebrow">Request supervision</span>
                <h2>Arrange a visit</h2>
                @if($assignment)
                    <form method="POST" action="{{ route('student.visits.store') }}">
                        @csrf
                        <div class="field">
                            <label for="requested_for">Preferred date and time</label>
                            <input class="input" id="requested_for" name="requested_for" type="datetime-local" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="field">
                            <label for="location">Visit location</label>
                            <input class="input" id="location" name="location" value="Kampala" required maxlength="180">
                        </div>
                        <div class="field">
                            <label for="notes">Notes</label>
                            <textarea class="input" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Reason for the visit, host details or concerns."></textarea>
                        </div>
                        <button class="button">Request a visit</button>
                    </form>
                @else
                    <p class="muted">A supervisor must be assigned before requesting a visit.</p>
                @endif

                <div class="list" style="margin-top:16px">
                    @foreach($visits as $visit)
                        <div class="item">
                            <div class="row">
                                <b>{{ $visit->requested_for->format('j M Y, g:i a') }}</b>
                                <span class="tag">{{ ucfirst($visit->status) }}</span>
                            </div>
                            <p>{{ $visit->location }}@if($visit->notes) · {{ $visit->notes }}@endif</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
