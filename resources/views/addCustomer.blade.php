@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Add Customer</div>

                <div class="panel-body">
                    <form action="{{route('customer.add')}}" method="post">
                    {{ csrf_field() }}
                    <label for="">First Name:</label>
                    <input type="text" name="first_name" /><br />
                    <label for="">Last Name:</label>
                    <input type="text" name="last_name" /><br />
                    <label for="">Phone:</label>
                    <input type="text" name="phone" /><br />
                    <button type="submit">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
