<!-- resources/views/admin/pincode/actions.blade.php -->
 @if ($data->status =="Delivered" || $data->status =="delivered")
                      <a href="{{route('Booking.show',$data->id)}}" class="btn btn-sm"><i class="fas fa-copy"></i> View</a>
                     
                    @else
                        <a href="{{route('Booking.show',$data->id)}}" class="btn btn-sm"><i class="fas fa-edit"></i> Edit</a>
                    @endif
                     <a href="{{route('upload-pod',$data->id)}}" class="btn btn-sm"><i class="fas fa-upload"></i> Upload Pod</a>
                        <a href="{{route('Booking-invoice',$data->id)}}" target="_blank"  class="btn btn-sm"><i class="fas fa-print"></i> Print</a>
                           <a href="{{route('Booking-wareeinvoice',$data->id)}}" target="_blank"  class="btn btn-sm"><i class="fas fa-print"></i> Waree Print</a>
                    
                           <a href="{{route('delete-booking',$data->id)}}" class="btn btn-sm"><i class="fas fa-trash"></i> Delete</a>
            <!--               <button class="btn btn-sm btn-primary bluedart-btn" data-id="${row.id}">-->
            <!--    BLUEDART-->
            <!--</button>-->

                