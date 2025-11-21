<!-- resources/views/admin/pincode/actions.blade.php -->
<form method="post" action="{{ route('pincode.destroy', $data->id) }}">
    <a href="{{ route('pincode.show', $data->id) }}" class="btn btn-sm"><i class="fas fa-edit"></i>Edit</a>
    @method('delete')
    @csrf
    <button type="submit" class="btn btn-sm"><i class="fas fa-trash"></i>Delete</button>
</form>
