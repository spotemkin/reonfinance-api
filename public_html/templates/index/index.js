function module_switch(id)
{
    let i = document.getElementById(id).style.display;

    if ((i !== ''))
        i = '';
    else
        i = 'inherit';

    let elements = document.querySelectorAll('.one_module_methods');

    for (let elem of elements)
    {
        elem.style.display = '';
    }

    document.getElementById(id).style.display = i;
}
