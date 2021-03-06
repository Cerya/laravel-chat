<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\ChatMessage;
use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function listen()
    {
        return view('listen');
    }

    /**
     * Start a conversation with a representative
     */
    public function start()
    {
        return view('start');
    }

    /**
     * Renders a customer-initiated conversation view
     *
     * @param int $conversationId
     * @param int $representativeId
     * @return \Illuminate\Http\Response
     */
    public function chat($conversationId, $representativeId)
    {
        $user = Auth::user();
        $customerName = '';
        $customerEmail = '';

        // Initiate the conversation if the current user is a customer
        $chatMessage = $user->sentMessages()->create([
            'sender_id' => $user->id,
            'receiver_id' => $representativeId,
            'conversation_id' => $conversationId,
            'message' => $user->name . ' joined the conversation!',
            'sender_name' => User::find($user->id)->name,
            'receiver_name' => User::find($representativeId)->name,
        ]);

        // Trigger the event to be broadcast
        broadcast(new ChatMessageSent($chatMessage))->toOthers();

        // Show the customer's email to the representative
        if ($user->type == UserController::USER_TYPE_REPRESENTATIVE) {
            $message = ChatMessage::where('conversation_id', $conversationId)->first();
            $customer = User::find($message->sender_id);
            $customerName = $customer->name;
            $customerEmail = $customer->email;
        }

        return view('chat',
            [
                'conversationId' => $conversationId,
                'receiverId' => $representativeId,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
            ]
        );
    }

    /**
     * Renders a list of conversations
     *
     * @return \Illuminate\Http\Response
     */
    public function conversations()
    {
        return view('conversations', ['representativeId' => Auth::user()->id]);
    }

    /**
     * Renders all messages in a given conversation
     *
     * @param int $conversationId
     * @return \Illuminate\Http\Response
     */
    public function conversation($conversationId)
    {
        return view('conversation', ['conversationId' => $conversationId]);
    }

    /**
     * Renders a list of representatives
     *
     * @return \Illuminate\Http\Response
     */
    public function representatives()
    {
        return view('representatives');
    }
}
